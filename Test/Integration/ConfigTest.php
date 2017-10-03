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

// @codingStandardsIgnoreStart

namespace Taxjar\SalesTax\Test\Integration;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\ObjectManager;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    private $moduleName = 'Taxjar_SalesTax';

    public function testModuleIsRegistered()
    {
        /**
         * @var ComponentRegistrar $registrar
         */
        $registrar = new ComponentRegistrar();

        $this->assertArrayHasKey($this->moduleName, $registrar->getPaths(ComponentRegistrar::MODULE));
    }

    public function testModuleIsConfiguredAndEnabled()
    {
        /**
         * @var ObjectManager $objectManager
         */
        $objectManager = ObjectManager::getInstance();

        /**
         * @var ModuleList $moduleList
         */
        $moduleList = $objectManager->create(ModuleList::class);

        $this->assertTrue($moduleList->has($this->moduleName));
    }
}
