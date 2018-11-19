<?php

namespace Enqueue\Monitoring\Tests;

use Enqueue\Monitoring\GenericStatsStorageFactory;
use Enqueue\Monitoring\InfluxDbStorage;
use Enqueue\Monitoring\StatsStorageFactory;
use Enqueue\Monitoring\WampStorage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class GenericStatsStorageFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementStatsStorageFactoryInterface()
    {
        $this->assertClassImplements(StatsStorageFactory::class, GenericStatsStorageFactory::class);
    }

    public function testShouldCreateInfluxDbStorage()
    {
        $storage = (new GenericStatsStorageFactory())->create('influxdb:');

        $this->assertInstanceOf(InfluxDbStorage::class, $storage);
    }

    public function testShouldCreateWampStorage()
    {
        $storage = (new GenericStatsStorageFactory())->create('wamp:');

        $this->assertInstanceOf(WampStorage::class, $storage);
    }

    public function testShouldThrowIfStorageIsNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A given scheme "unsupported" is not supported.');

        (new GenericStatsStorageFactory())->create('unsupported:');
    }
}
