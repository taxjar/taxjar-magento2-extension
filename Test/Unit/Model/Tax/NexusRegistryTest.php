<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Tax;

use Taxjar\SalesTax\Model\Tax\Nexus;
use Taxjar\SalesTax\Model\Tax\NexusFactory;
use Taxjar\SalesTax\Model\Tax\NexusRegistry;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class NexusRegistryTest extends UnitTestCase
{
    public function testRegisterNexusWithNullIdDoesNotCrash()
    {
        $nexusFactory = $this->createStub(NexusFactory::class);
        $registry = new NexusRegistry($nexusFactory);

        $nexus = $this->createStub(Nexus::class);
        $nexus->method('getId')->willReturn(null);

        // Should not throw
        $registry->registerNexus($nexus);

        $this->assertTrue(true);
    }

    public function testRegisterNexusWithValidIdStoresModel()
    {
        $nexusFactory = $this->createStub(NexusFactory::class);
        $registry = new NexusRegistry($nexusFactory);

        $nexus = $this->createStub(Nexus::class);
        $nexus->method('getId')->willReturn(42);

        $registry->registerNexus($nexus);

        $registryData = $this->getProperty($registry, 'nexusRegistryById');
        $this->assertArrayHasKey(42, $registryData);
        $this->assertSame($nexus, $registryData[42]);
    }
}
