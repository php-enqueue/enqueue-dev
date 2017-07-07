<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;

class StompConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, StompConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new StompConnectionFactory([]);

        $this->assertAttributeEquals([
            'host' => null,
            'port' => null,
            'login' => null,
            'password' => null,
            'vhost' => null,
            'buffer_size' => 1000,
            'connection_timeout' => 1,
            'sync' => false,
            'lazy' => true,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new StompConnectionFactory(['host' => 'theCustomHost']);

        $this->assertAttributeEquals([
            'host' => 'theCustomHost',
            'port' => null,
            'login' => null,
            'password' => null,
            'vhost' => null,
            'buffer_size' => 1000,
            'connection_timeout' => 1,
            'sync' => false,
            'lazy' => true,
        ], 'config', $factory);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new StompConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(StompContext::class, $context);

        $this->assertAttributeEquals(null, 'stomp', $context);
        $this->assertInternalType('callable', $this->readAttribute($context, 'stompFactory'));
    }
}
