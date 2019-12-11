<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpContext;
use Enqueue\AmqpLib\AmqpSubscriptionConsumer;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpSubscriptionConsumerTest extends TestCase
{
    public function testShouldImplementSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(AmqpSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(SubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithAmqpContextAndHeartbeatOnTickAsArguments()
    {
        new AmqpSubscriptionConsumer($this->createAmqpContextMock(), $heartbeatOnTick = true);
    }

    /**
     * @return AmqpContext|MockObject
     */
    private function createAmqpContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }
}
