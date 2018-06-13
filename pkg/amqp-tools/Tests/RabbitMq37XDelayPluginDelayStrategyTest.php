<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\DelayStrategy;
use Enqueue\AmqpTools\RabbitMq37XDelayPluginDelayStrategy;
use Interop\Amqp\Impl\AmqpMessage;

class RabbitMq37XDelayPluginDelayStrategyTest extends RabbitMqDelayPluginDelayStrategyTest
{
    public function testShouldImplementDelayStrategyInterface()
    {
        $this->assertClassImplements(DelayStrategy::class, RabbitMq37XDelayPluginDelayStrategy::class);
    }

    protected function buildStrategy(): DelayStrategy
    {
        return new RabbitMq37XDelayPluginDelayStrategy();
    }

    protected function assertXDelay(AmqpMessage $delayedMessage, int $xDelay): void
    {
        $this->assertSame(['x-delay' => $xDelay], $delayedMessage->getHeaders());
    }
}
