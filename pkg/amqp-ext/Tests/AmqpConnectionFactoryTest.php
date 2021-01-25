<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class AmqpConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, AmqpConnectionFactory::class);
    }

    public function testShouldSetRabbitMqDlxDelayStrategyIfRabbitMqSchemeExtensionPresent()
    {
        $factory = new AmqpConnectionFactory('amqp+rabbitmq:');

        $this->assertAttributeInstanceOf(RabbitMqDlxDelayStrategy::class, 'delayStrategy', $factory);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new AmqpConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(AmqpContext::class, $context);

        $this->assertAttributeEquals(null, 'extChannel', $context);
        self::assertIsCallable($this->readAttribute($context, 'extChannelFactory'));
    }
}
