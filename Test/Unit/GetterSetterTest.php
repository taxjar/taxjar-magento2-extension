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

namespace Taxjar\SalesTax\Test\Unit;

class GetterSetterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $className
     * @param array $variables
     * @dataProvider dataProviderGettersSetters
     */
    public function testGettersSetters($className = null, $variables = null)
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $classObject = $objectManager->getObject($className);

        foreach ($variables as $variableName => $variableValue) {
            $setterName = 'set' . $variableName;

            $this->assertTrue(
                method_exists($classObject, $setterName),
                "Method " . $setterName . " does not exist in " . $className
            );

            if (is_array($variableValue)) {
                if (strpos($variableValue[0], 'Magento') !== false) {
                    $obj = $objectManager->getObject($variableValue[0]);
                    $variableValue = [$obj];
                    $variables[$variableName] = $variableValue;
                }
            } elseif (strpos($variableValue, 'Magento') !== false) {
                $obj = $objectManager->getObject($variableValue);
                $variableValue = $obj;
                $variables[$variableName] = $variableValue;
            }
            $this->assertNotFalse(
                call_user_func(
                    [$classObject, $setterName],
                    $variableValue
                ),
                "Calling method " . $setterName . " failed in " . $className
            );
        }

        foreach ($variables as $variableName => $variableValue) {
            $getterName = 'get' . $variableName;

            $this->assertTrue(
                method_exists($classObject, $getterName),
                "Method " . $getterName . " does not exist in " . $className
            );
            $result = call_user_func([$classObject, $getterName]);
            $this->assertNotFalse(
                $result,
                "Calling method " . $getterName . " failed in " . $className
            );
            $this->assertSame(
                $result,
                $variableValue,
                "Value from " . $getterName . "did not match in " . $className
            );
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderGettersSetters()
    {
        return [
            [
                'Taxjar\SalesTax\Model\Tax\Nexus',
                [
                    'Id' => 1,
                    'ApiId' => 1,
                    'Street' => '1280 Wilshire Blvd',
                    'City' => 'Santa Monica',
                    'CountryId' => 'US',
                    'Region' => 'California',
                    'RegionId' => 1,
                    'RegionCode' => 'CA',
                    'Postcode' => '90404',
                    'CreatedAt' => '2016-06-10 22:42:16',
                    'UpdatedAt' => '2016-06-10 22:42:16'
                ]
            ]
        ];
    }
}
