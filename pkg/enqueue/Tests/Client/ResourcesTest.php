<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\Driver\RedisDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Resources;
use PHPUnit\Framework\TestCase;

class ResourcesTest extends TestCase
{
    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(Resources::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testShouldConstructorBePrivate()
    {
        $rc = new \ReflectionClass(Resources::class);

        $this->assertTrue($rc->getConstructor()->isPrivate());
    }

    public function testShouldGetAvailableDriverInExpectedFormat()
    {
        $availableDrivers = Resources::getAvailableDrivers();

        $this->assertInternalType('array', $availableDrivers);
        $this->assertArrayHasKey(RedisDriver::class, $availableDrivers);

        $driverInfo = $availableDrivers[RedisDriver::class];
        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['redis'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame([], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['enqueue/enqueue', 'enqueue/redis'], $driverInfo['packages']);
    }

    public function testShouldGetAvailableDriverWithRequiredExtensionInExpectedFormat()
    {
        $availableDrivers = Resources::getAvailableDrivers();

        $this->assertInternalType('array', $availableDrivers);
        $this->assertArrayHasKey(RabbitMqDriver::class, $availableDrivers);

        $driverInfo = $availableDrivers[RabbitMqDriver::class];
        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['amqp', 'amqps'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame(['rabbitmq'], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['enqueue/enqueue', 'enqueue/amqp-bunny'], $driverInfo['packages']);
    }

    public function testShouldGetKnownDriversInExpectedFormat()
    {
        $knownDrivers = Resources::getKnownDrivers();

        $this->assertInternalType('array', $knownDrivers);
        $this->assertArrayHasKey(RedisDriver::class, $knownDrivers);

        $driverInfo = $knownDrivers[RedisDriver::class];
        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['redis'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame([], $driverInfo['requiredSchemeExtensions']);
    }

    public function testThrowsIfDriverClassNotImplementDriverFactoryInterfaceOnAddDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver class "stdClass" must implement "Enqueue\Client\DriverInterface" interface.');

        Resources::addDriver(\stdClass::class, [], [], ['foo']);
    }

    public function testThrowsIfNoSchemesProvidedOnAddDriver()
    {
        $driverClass = $this->getMockClass(DriverInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schemes could not be empty.');

        Resources::addDriver($driverClass, [], [], ['foo']);
    }

    public function testThrowsIfNoPackageProvidedOnAddDriver()
    {
        $driverClass = $this->getMockClass(DriverInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Packages could not be empty.');

        Resources::addDriver($driverClass, ['foo'], [], []);
    }

    public function testShouldAllowRegisterDriverThatIsNotInstalled()
    {
        Resources::addDriver('theDriverClass', ['foo'], ['barExtension'], ['foo']);

        $knownDrivers = Resources::getKnownDrivers();
        $this->assertInternalType('array', $knownDrivers);
        $this->assertArrayHasKey('theDriverClass', $knownDrivers);

        $availableDrivers = Resources::getAvailableDrivers();

        $this->assertInternalType('array', $availableDrivers);
        $this->assertArrayNotHasKey('theDriverClass', $availableDrivers);
    }

    public function testShouldAllowGetPreviouslyRegisteredDriver()
    {
        $driverClass = $this->getMockClass(DriverInterface::class);

        Resources::addDriver(
            $driverClass,
            ['fooscheme', 'barscheme'],
            ['fooextension', 'barextension'],
            ['foo/bar']
        );

        $availableDrivers = Resources::getAvailableDrivers();

        $this->assertInternalType('array', $availableDrivers);
        $this->assertArrayHasKey($driverClass, $availableDrivers);

        $driverInfo = $availableDrivers[$driverClass];
        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['fooscheme', 'barscheme'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame(['fooextension', 'barextension'], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['foo/bar'], $driverInfo['packages']);
    }
}
