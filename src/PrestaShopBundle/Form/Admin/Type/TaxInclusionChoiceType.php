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

namespace PrestaShopBundle\Form\Admin\Type;

use PrestaShop\PrestaShop\Core\Form\ChoiceProvider\TaxInclusionChoiceProvider;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxInclusionChoiceType extends ChoiceType
{
    /**
     * @var TaxInclusionChoiceProvider
     */
    private $taxInclusionChoiceProvider;

    public function __construct(
        TaxInclusionChoiceProvider $shippingInclusionChoiceProvider
    ) {
        parent::__construct();
        $this->taxInclusionChoiceProvider = $shippingInclusionChoiceProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label' => false,
            'choices' => $this->taxInclusionChoiceProvider->getChoices(),
            'placeholder' => false,
            'required' => false,
            'row_attr' => [
                'class' => 'js-include-tax-row',
            ],
        ]);
    }
}
