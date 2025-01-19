<?php

namespace Enqueue\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Resources;
use Enqueue\Wamp\WampConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Exception\LogicException;

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

    public function testShouldGetAvailableConnectionsInExpectedFormat()
    {
        $availableConnections = Resources::getAvailableConnections();

        self::assertIsArray($availableConnections);
        $this->assertArrayHasKey(RedisConnectionFactory::class, $availableConnections);

        $connectionInfo = $availableConnections[RedisConnectionFactory::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['redis', 'rediss'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame(['predis', 'phpredis'], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('enqueue/redis', $connectionInfo['package']);
    }

    public function testShouldGetKnownConnectionsInExpectedFormat()
    {
        $availableConnections = Resources::getKnownConnections();

        self::assertIsArray($availableConnections);
        $this->assertArrayHasKey(RedisConnectionFactory::class, $availableConnections);

        $connectionInfo = $availableConnections[RedisConnectionFactory::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['redis', 'rediss'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame(['predis', 'phpredis'], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('enqueue/redis', $connectionInfo['package']);
    }

    public function testThrowsIfConnectionClassNotImplementConnectionFactoryInterfaceOnAddConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The connection factory class "stdClass" must implement "Interop\Queue\ConnectionFactory" interface.');

        Resources::addConnection(\stdClass::class, [], [], 'foo');
    }

    public function testThrowsIfNoSchemesProvidedOnAddConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schemes could not be empty.');

        Resources::addConnection(ConnectionFactory::class, [], [], 'foo');
    }

    public function testThrowsIfNoPackageProvidedOnAddConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package name could not be empty.');

        Resources::addConnection(ConnectionFactory::class, ['foo'], [], '');
    }

    public function testShouldAllowRegisterConnectionThatIsNotInstalled()
    {
        Resources::addConnection('theConnectionClass', ['foo'], [], 'foo');

        $knownConnections = Resources::getKnownConnections();
        self::assertIsArray($knownConnections);
        $this->assertArrayHasKey('theConnectionClass', $knownConnections);

        $availableConnections = Resources::getAvailableConnections();

        self::assertIsArray($availableConnections);
        $this->assertArrayNotHasKey('theConnectionClass', $availableConnections);
    }

    public function testShouldAllowGetPreviouslyRegisteredConnection()
    {
        $connectionClass = new class implements ConnectionFactory {
            public function createContext(): Context
            {
                throw new LogicException('not implemented');
            }
        };

        Resources::addConnection(
            $connectionClass::class,
            ['fooscheme', 'barscheme'],
            ['fooextension', 'barextension'],
            'foo/bar'
        );

        $availableConnections = Resources::getAvailableConnections();

        self::assertIsArray($availableConnections);
        $this->assertArrayHasKey($connectionClass::class, $availableConnections);

        $connectionInfo = $availableConnections[$connectionClass::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['fooscheme', 'barscheme'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame(['fooextension', 'barextension'], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('foo/bar', $connectionInfo['package']);
    }

    public function testShouldHaveRegisteredWampConfiguration()
    {
        $availableConnections = Resources::getKnownConnections();

        self::assertIsArray($availableConnections);
        $this->assertArrayHasKey(WampConnectionFactory::class, $availableConnections);

        $connectionInfo = $availableConnections[WampConnectionFactory::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['wamp', 'ws'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame([], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('enqueue/wamp', $connectionInfo['package']);
    }
}
