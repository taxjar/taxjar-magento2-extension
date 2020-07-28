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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Controller\Adminhtml\Report;

class Index extends \Taxjar\SalesTax\Controller\Adminhtml\Report
{
    public function execute()
    {
        $date = date('Y-m-d');
        $fileName = 'taxjar-report-' . $date . '.json';
        $data = $this->generateReport();

        try {
            return $this->fileFactory->create($fileName, $data);
        } catch (\Exception $e) {
            return $this->fileFactory->create($fileName, 'An error occurred');
        }
    }

    protected function generateReport() {
        return
'{
    "name": "report"
}';
    }
}
