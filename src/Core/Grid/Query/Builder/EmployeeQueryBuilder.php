<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
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
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Grid\Query\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Class EmployeeQueryBuilder builds queries for Employees grid.
 */
final class EmployeeQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var string
     */
    private $contextIdLang;

    /**
     * @var int[]
     */
    private $contextShopIds;

    /**
     * @param Connection $connection
     * @param $dbPrefix
     * @param DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator
     * @param string $contextIdLang
     * @param int[] $contextShopIds
     */
    public function __construct(
        Connection $connection,
        $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        $contextIdLang,
        array $contextShopIds
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextIdLang = $contextIdLang;
        $this->contextShopIds = $contextShopIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $searchQueryBuilder = $this->getEmployeeQueryBuilder($searchCriteria)
            ->select('e.*, pl.name as profile_name')
        ;

        $this->searchCriteriaApplicator
            ->applySorting($searchCriteria, $searchQueryBuilder)
            ->applyPagination($searchCriteria, $searchQueryBuilder)
        ;

        return $searchQueryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $countQueryBuilder = $this->getEmployeeQueryBuilder($searchCriteria)
            ->select('COUNT(e.id_profile)')
        ;

        return $countQueryBuilder;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return QueryBuilder
     */
    private function getEmployeeQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $sub = $this->connection->createQueryBuilder()
            ->select(1)
            ->from($this->dbPrefix . 'employee_shop', 'es')
            ->where('e.id_employee = es.id_employee')
            ->andWhere('es.id_shop IN (:context_shop_ids)')
        ;

        $qb = $this->connection->createQueryBuilder()
            ->from($this->dbPrefix .'employee', 'e')
            ->leftJoin(
                'e',
                $this->dbPrefix . 'profile_lang',
                'pl', 'e.id_profile = pl.id_profile AND pl.id_lang = ' . (int) $this->contextIdLang
            )
            ->andWhere('EXISTS (' . $sub->getSQL() . ')')
            ->setParameter('context_shop_ids', $this->contextShopIds, Connection::PARAM_INT_ARRAY)
        ;

        $this->applyFilters($qb, $searchCriteria->getFilters());

        return $qb;
    }

    /**
     * Apply filters for Query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $filters
     */
    private function applyFilters(QueryBuilder $queryBuilder, array $filters)
    {
        foreach ($filters as $filterName => $filterValue) {

        }
    }
}
