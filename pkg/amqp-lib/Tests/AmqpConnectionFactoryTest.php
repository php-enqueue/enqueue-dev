<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
}
