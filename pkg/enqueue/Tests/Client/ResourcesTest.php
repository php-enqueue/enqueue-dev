<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverSendResult;
use Enqueue\Client\Message;
use Enqueue\Client\Resources;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Queue as InteropQueue;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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

        self::assertIsArray($availableDrivers);
        $this->assertGreaterThan(0, count($availableDrivers));

        $driverInfo = $availableDrivers[0];

        $this->assertArrayHasKey('driverClass', $driverInfo);
        $this->assertSame(AmqpDriver::class, $driverInfo['driverClass']);

        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['amqp', 'amqps'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame([], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['enqueue/enqueue', 'enqueue/amqp-bunny'], $driverInfo['packages']);
    }

    public function testShouldGetAvailableDriverWithRequiredExtensionInExpectedFormat()
    {
        $availableDrivers = Resources::getAvailableDrivers();

        self::assertIsArray($availableDrivers);
        $this->assertGreaterThan(0, count($availableDrivers));

        $driverInfo = $availableDrivers[1];

        $this->assertArrayHasKey('driverClass', $driverInfo);
        $this->assertSame(RabbitMqDriver::class, $driverInfo['driverClass']);

        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['amqp', 'amqps'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame(['rabbitmq'], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['enqueue/enqueue', 'enqueue/amqp-bunny'], $driverInfo['packages']);
    }

    public function testShouldGetKnownDriversInExpectedFormat()
    {
        $knownDrivers = Resources::getAvailableDrivers();

        self::assertIsArray($knownDrivers);
        $this->assertGreaterThan(0, count($knownDrivers));

        $driverInfo = $knownDrivers[0];

        $this->assertArrayHasKey('driverClass', $driverInfo);
        $this->assertSame(AmqpDriver::class, $driverInfo['driverClass']);

        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['amqp', 'amqps'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame([], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['enqueue/enqueue', 'enqueue/amqp-bunny'], $driverInfo['packages']);
    }

    public function testThrowsIfDriverClassNotImplementDriverFactoryInterfaceOnAddDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver class "stdClass" must implement "Enqueue\Client\DriverInterface" interface.');

        Resources::addDriver(\stdClass::class, [], [], ['foo']);
    }

    public function testThrowsIfNoSchemesProvidedOnAddDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schemes could not be empty.');

        Resources::addDriver(DriverInterface::class, [], [], ['foo']);
    }

    public function testThrowsIfNoPackageProvidedOnAddDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Packages could not be empty.');

        Resources::addDriver(DriverInterface::class, ['foo'], [], []);
    }

    public function testShouldAllowRegisterDriverThatIsNotInstalled()
    {
        Resources::addDriver('theDriverClass', ['foo'], ['barExtension'], ['foo']);

        $availableDrivers = Resources::getKnownDrivers();

        $driverInfo = end($availableDrivers);

        $this->assertSame('theDriverClass', $driverInfo['driverClass']);
    }

    public function testShouldAllowGetPreviouslyRegisteredDriver()
    {
        $driverClass = new class implements DriverInterface {
            public function createTransportMessage(Message $message): InteropMessage
            {
                throw new \LogicException('not implemented');
            }

            public function createClientMessage(InteropMessage $message): Message
            {
                throw new \LogicException('not implemented');
            }

            public function sendToRouter(Message $message): DriverSendResult
            {
                throw new \LogicException('not implemented');
            }

            public function sendToProcessor(Message $message): DriverSendResult
            {
                throw new \LogicException('not implemented');
            }

            public function createQueue(string $queueName, bool $prefix = true): InteropQueue
            {
                throw new \LogicException('not implemented');
            }

            public function createRouteQueue(Route $route): InteropQueue
            {
                throw new \LogicException('not implemented');
            }

            public function setupBroker(?LoggerInterface $logger = null): void
            {
                throw new \LogicException('not implemented');
            }

            public function getConfig(): Config
            {
                throw new \LogicException('not implemented');
            }

            public function getContext(): Context
            {
                throw new \LogicException('not implemented');
            }

            public function getRouteCollection(): RouteCollection
            {
                throw new \LogicException('not implemented');
            }
        };

        Resources::addDriver(
            $driverClass::class,
            ['fooscheme', 'barscheme'],
            ['fooextension', 'barextension'],
            ['foo/bar']
        );

        $availableDrivers = Resources::getAvailableDrivers();

        $driverInfo = end($availableDrivers);

        $this->assertArrayHasKey('driverClass', $driverInfo);
        $this->assertSame($driverClass::class, $driverInfo['driverClass']);

        $this->assertArrayHasKey('schemes', $driverInfo);
        $this->assertSame(['fooscheme', 'barscheme'], $driverInfo['schemes']);

        $this->assertArrayHasKey('requiredSchemeExtensions', $driverInfo);
        $this->assertSame(['fooextension', 'barextension'], $driverInfo['requiredSchemeExtensions']);

        $this->assertArrayHasKey('packages', $driverInfo);
        $this->assertSame(['foo/bar'], $driverInfo['packages']);
    }
}
