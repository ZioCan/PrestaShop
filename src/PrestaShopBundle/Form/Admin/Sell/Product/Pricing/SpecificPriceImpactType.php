<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Form\Admin\Sell\Product\Pricing;

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\Reduction;
use PrestaShop\PrestaShop\Core\Domain\Product\SpecificPrice\ValueObject\InitialPrice;
use PrestaShop\PrestaShop\Core\Domain\ValueObject\Reduction as ReductionVO;
use PrestaShopBundle\Form\Admin\Type\PriceReductionType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class SpecificPriceImpactType extends TranslatorAwareType
{
    // We need specific groups because the constraints are different when fields are disabled
    private const FIXED_PRICE_GROUP = 'fixed_price_group';
    private const REDUCTION_GROUP = 'reduction_group';

    /**
     * @var string
     */
    private $defaultCurrencyIso;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        string $defaultCurrencyIso
    ) {
        parent::__construct($translator, $locales);
        $this->defaultCurrencyIso = $defaultCurrencyIso;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reduction', PriceReductionType::class, [
                'label' => $this->trans('Apply a discount to the initial price', 'Admin.Catalog.Feature'),
                'label_subtitle' => 'For customers meeting the conditions, the initial price will be crossed out and the discount will be highlighted.',
                'required' => false,
                'constraints' => [
                    new Reduction([
                        'invalidPercentageValueMessage' => $this->trans(
                            'Reduction value "%value%" is invalid. Allowed values from 0 to %max%',
                            'Admin.Notifications.Error',
                            ['%max%' => ReductionVO::MAX_ALLOWED_PERCENTAGE . '%']
                        ),
                        'invalidAmountValueMessage' => $this->trans(
                            'Reduction value "%value%" is invalid. Value cannot be negative',
                            'Admin.Notifications.Error'
                        ),
                        'groups' => [self::REDUCTION_GROUP],
                    ]),
                ],
                'disabling_switch' => true,
                'disabling_switch_event' => 'switchReductionSpecificPrice',
                'disabled_value' => function ($data, FormInterface $form): bool {
                    return $this->shouldBeDisabled($form);
                },
            ])
            ->add('fixed_price_tax_excluded', MoneyType::class, [
                'required' => false,
                'label' => $this->trans('Set specific price', 'Admin.Catalog.Feature'),
                'label_subtitle' => $this->trans('Retail price (tax excl.)', 'Admin.Catalog.Feature'),
                'attr' => ['data-display-price-precision' => self::PRESTASHOP_DECIMALS],
                'row_attr' => [
                    'class' => 'js-fixed-price-row',
                ],
                'currency' => $this->defaultCurrencyIso,
                'constraints' => [
                    new NotBlank(['groups' => [self::FIXED_PRICE_GROUP]]),
                    new Type(['type' => 'float', 'groups' => [self::FIXED_PRICE_GROUP]]),
                    new PositiveOrZero(['groups' => [self::FIXED_PRICE_GROUP]]),
                ],
                'disabling_switch' => true,
                'disabling_switch_event' => 'switchFixedSpecificPrice',
                'disabled_value' => function ($data, FormInterface $form): bool {
                    return $this->shouldBeDisabled($form);
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'label' => $this->trans('Impact on price', 'Admin.Catalog.Feature'),
            'label_subtitle' => $this->trans('At least one of the following must be activated', 'Admin.Catalog.Feature'),
            'label_tag_name' => 'h4',
            'validation_groups' => function (FormInterface $form): array {
                $useFixedPrice = $this->isUsingFixedPrice($form->getData());

                return $useFixedPrice ? [self::FIXED_PRICE_GROUP] : [self::REDUCTION_GROUP];
            },
        ]);
    }

    private function shouldBeDisabled(FormInterface $form): bool
    {
        $formName = $form->getName();
        $impactForm = $form->getParent();
        $impactData = $impactForm->getData();
        $useFixedPrice = $this->isUsingFixedPrice($impactData);

        // The field van either be reduction or fixed_price_tax_excluded, whe fixed price is used the reduction is disabled
        if ($formName === 'reduction') {
            return $useFixedPrice;
        }

        return !$useFixedPrice;
    }

    /**
     * Check if fixed price is being setup, the fixed price is based on fixed_price_tax_excluded so if it is absent
     * or if its value equals -1 no fixed price is defined.
     *
     * However, the most trustable value is the one from the checkbox, so it is used as priority.
     *
     * @param array|null $impactData
     *
     * @return bool
     */
    private function isUsingFixedPrice(?array $impactData): bool
    {
        if (array_key_exists('disabling_switch_fixed_price_tax_excluded', $impactData)) {
            return $impactData['disabling_switch_fixed_price_tax_excluded'] === true;
        }

        // Use array_key_exists because fixed_price_tax_excluded can be present but null, it doesn't mean it is not
        // used it is just empty
        if (!array_key_exists('fixed_price_tax_excluded', $impactData)) {
            return false;
        }

        // Use 0 as fallback in case the value is null, because empty string throws an exception in DecimalNumber used
        // in InitialPrice::isInitialPriceValue
        $fixedPrice = $impactData['fixed_price_tax_excluded'] ?? 0;

        return !InitialPrice::isInitialPriceValue((string) $fixedPrice);
    }
}
