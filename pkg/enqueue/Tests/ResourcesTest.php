<?php

namespace Enqueue\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Resources;
use Interop\Queue\ConnectionFactory;
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

    public function testShouldGetAvailableConnectionsInExpectedFormat()
    {
        $availableConnections = Resources::getAvailableConnections();

        $this->assertInternalType('array', $availableConnections);
        $this->assertArrayHasKey(RedisConnectionFactory::class, $availableConnections);

        $connectionInfo = $availableConnections[RedisConnectionFactory::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['redis'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame(['predis', 'phpredis'], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('enqueue/redis', $connectionInfo['package']);
    }

    public function testShouldGetKnownConnectionsInExpectedFormat()
    {
        $availableConnections = Resources::getKnownConnections();

        $this->assertInternalType('array', $availableConnections);
        $this->assertArrayHasKey(RedisConnectionFactory::class, $availableConnections);

        $connectionInfo = $availableConnections[RedisConnectionFactory::class];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['redis'], $connectionInfo['schemes']);

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
        $connectionClass = $this->getMockClass(ConnectionFactory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schemes could not be empty.');

        Resources::addConnection($connectionClass, [], [], 'foo');
    }

    public function testThrowsIfNoPackageProvidedOnAddConnection()
    {
        $connectionClass = $this->getMockClass(ConnectionFactory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package name could not be empty.');

        Resources::addConnection($connectionClass, ['foo'], [], '');
    }

    public function testShouldAllowRegisterConnectionThatIsNotInstalled()
    {
        Resources::addConnection('theConnectionClass', ['foo'], [], 'foo');

        $knownConnections = Resources::getKnownConnections();
        $this->assertInternalType('array', $knownConnections);
        $this->assertArrayHasKey('theConnectionClass', $knownConnections);

        $availableConnections = Resources::getAvailableConnections();

        $this->assertInternalType('array', $availableConnections);
        $this->assertArrayNotHasKey('theConnectionClass', $availableConnections);
    }

    public function testShouldAllowGetPreviouslyRegisteredConnection()
    {
        $connectionClass = $this->getMockClass(ConnectionFactory::class);

        Resources::addConnection(
            $connectionClass,
            ['fooscheme', 'barscheme'],
            ['fooextension', 'barextension'],
            'foo/bar'
        );

        $availableConnections = Resources::getAvailableConnections();

        $this->assertInternalType('array', $availableConnections);
        $this->assertArrayHasKey($connectionClass, $availableConnections);

        $connectionInfo = $availableConnections[$connectionClass];
        $this->assertArrayHasKey('schemes', $connectionInfo);
        $this->assertSame(['fooscheme', 'barscheme'], $connectionInfo['schemes']);

        $this->assertArrayHasKey('supportedSchemeExtensions', $connectionInfo);
        $this->assertSame(['fooextension', 'barextension'], $connectionInfo['supportedSchemeExtensions']);

        $this->assertArrayHasKey('package', $connectionInfo);
        $this->assertSame('foo/bar', $connectionInfo['package']);
    }
}
