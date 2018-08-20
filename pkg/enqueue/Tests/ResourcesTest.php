<?php

namespace Enqueue\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Resources;
use Interop\Queue\PsrConnectionFactory;
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

    public function testThrowsIfConnectionClassNotExistsOnAddConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The connection factory class "classNotExist" does not exist.');

        Resources::addConnection('classNotExist', [], [], 'foo');
    }

    public function testThrowsIfConnectionClassNotImplementConnectionFactoryInterfaceOnAddConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The connection factory class "stdClass" must implement "Interop\Queue\PsrConnectionFactory" interface.');

        Resources::addConnection(\stdClass::class, [], [], 'foo');
    }

    public function testThrowsIfNoSchemesProvidedOnAddConnection()
    {
        $connectionClass = $this->getMockClass(PsrConnectionFactory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schemes could not be empty.');

        Resources::addConnection($connectionClass, [], [], 'foo');
    }

    public function testThrowsIfNoPackageProvidedOnAddConnection()
    {
        $connectionClass = $this->getMockClass(PsrConnectionFactory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package name could not be empty.');

        Resources::addConnection($connectionClass, ['foo'], [], '');
    }

    public function testShouldAllowGetPreviouslyRegisteredConnection()
    {
        $connectionClass = $this->getMockClass(PsrConnectionFactory::class);

        Resources::addConnection(
            $connectionClass,
            ['fooscheme', 'barscheme'],
            ['fooextension', 'barextension'],
            'foo/bar'
        );

        $availableConnections = Resources::getKnownConnections();

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
