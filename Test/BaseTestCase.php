<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BaseTestCase extends TestCase
{
    /**
     * Call protected or private method
     *
     * @param $object
     * @param $methodName
     * @param array $arguments
     * @return mixed
     */
    public function callMethod($object, $methodName, array $arguments = [])
    {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return empty($arguments)
            ? $method->invoke($object)
            : $method->invokeArgs($object, $arguments);
    }

    /**
     * Get protected or private property
     *
     * @param $object
     * @param $propertyName
     * @return mixed
     */
    public function getProperty($object, $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * @param $object
     * @param $propertyName
     * @param $value
     * @throws \ReflectionException
     */
    public function setProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);

        return $object;
    }
}
