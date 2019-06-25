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

    public function __construct(\Magento\Directory\Model\Config\Source\Allregion $allRegion)
    {
        $this->allRegion = $allRegion;
    }

    /**
     * Return options as an array
     * @return array
     */
    public function toOptionArray()
    {
        $regions = $this->allRegion->toOptionArray(true);

        foreach ($regions as $region) {
            if ($region['label'] == 'United States') {
                return $region['value'];
            }
        }

        return $regions;
    }

    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        return [];
    }
}