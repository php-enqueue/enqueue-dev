<?php

declare(strict_types=1);

namespace Enqueue\Monitoring\Tests;

use Enqueue\Monitoring\DatadogStorage;
use Enqueue\Monitoring\GenericStatsStorageFactory;
use Enqueue\Monitoring\InfluxDbStorage;
use Enqueue\Monitoring\StatsStorageFactory;
use Enqueue\Monitoring\WampStorage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class GenericStatsStorageFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementStatsStorageFactoryInterface(): void
    {
        $this->assertClassImplements(StatsStorageFactory::class, GenericStatsStorageFactory::class);
    }

    public function testShouldCreateInfluxDbStorage(): void
    {
        $storage = (new GenericStatsStorageFactory())->create('influxdb:');

        $this->assertInstanceOf(InfluxDbStorage::class, $storage);
    }

    public function testShouldCreateWampStorage(): void
    {
        $storage = (new GenericStatsStorageFactory())->create('wamp:');

        $this->assertInstanceOf(WampStorage::class, $storage);
    }

    public function testShouldCreateDatadogStorage(): void
    {
        $storage = (new GenericStatsStorageFactory())->create('datadog:');

        $this->assertInstanceOf(DatadogStorage::class, $storage);
    }

    public function testShouldThrowIfStorageIsNotSupported(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A given scheme "unsupported" is not supported.');

        (new GenericStatsStorageFactory())->create('unsupported:');
    }
}
