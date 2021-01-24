<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;

class StompConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, StompConnectionFactory::class);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new StompConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(StompContext::class, $context);

        $this->assertAttributeEquals(null, 'stomp', $context);
        $this->assertAttributeEquals(true, 'useExchangePrefix', $context);
        self::assertIsCallable($this->readAttribute($context, 'stompFactory'));
    }

    public function testShouldCreateRabbitMQContext()
    {
        $factory = new StompConnectionFactory('stomp+rabbitmq://');

        $context = $factory->createContext();

        $this->assertInstanceOf(StompContext::class, $context);

        $this->assertAttributeEquals(null, 'stomp', $context);
        $this->assertAttributeEquals(true, 'useExchangePrefix', $context);
    }

    public function testShouldCreateActiveMQContext()
    {
        $factory = new StompConnectionFactory('stomp+activemq://');

        $context = $factory->createContext();

        $this->assertInstanceOf(StompContext::class, $context);

        $this->assertAttributeEquals(null, 'stomp', $context);
        $this->assertAttributeEquals(false, 'useExchangePrefix', $context);
    }
}
