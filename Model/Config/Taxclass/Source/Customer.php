<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Config\Taxclass\Source;

use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

class Customer implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        TaxClassRepositoryInterface $taxClassRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->taxClassRepository = $taxClassRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Return options as an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $output = [];
        $filter = $this->filterBuilder
            ->setField(ClassModel::KEY_TYPE)
            ->setValue(TaxClassManagementInterface::TYPE_CUSTOMER)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter])->create();
        $customerClasses = $this->taxClassRepository->getList($searchCriteria);

        foreach ($customerClasses->getItems() as $taxClass) {
            $output[] = [
                'value' => $taxClass->getClassId(),
                'label' => $taxClass->getClassName(),
            ];
        }

        return $output;
    }
}
