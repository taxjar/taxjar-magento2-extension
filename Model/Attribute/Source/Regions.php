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

namespace Taxjar\SalesTax\Model\Attribute\Source;


class Regions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    private $allRegion;
    private $customer;
    private $request;

    public function __construct(
        \Magento\Directory\Model\Config\Source\Allregion $allRegion,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\RequestInterface $request
    )    {
        $this->allRegion = $allRegion;
        $this->customer = $customerFactory->create();
        $this->request = $request;
    }

    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        $customerId = $this->request->getParam('id');
        $customer = $this->customer->load($customerId);

        $regions = $this->allRegion->toOptionArray(true);

        foreach($regions as $region) {
            if($region['label'] == 'United States') {

                $regionIds = explode(',',$customer->getData('tj_regions'));

                foreach($region['value'] as $k => $state) {
                    if (in_array($state['value'], $regionIds)) {
                        $region['value'][$k]['selected'] = 'selected';
                    }
                }

                return [$region];
            }
        }

        return $regions;
    }
}