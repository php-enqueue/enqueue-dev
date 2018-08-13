<?php

namespace Enqueue\AmqpBunny\Tests;

use Enqueue\AmqpBunny\AmqpContext;
use Enqueue\AmqpBunny\AmqpSubscriptionConsumer;
use Interop\Queue\PsrSubscriptionConsumer;
use PHPUnit\Framework\TestCase;

class AmqpSubscriptionConsumerTest extends TestCase
{
    public function testShouldImplementPsrSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(AmqpSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(PsrSubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithAmqpContextAsFirstArgument()
    {
        new AmqpSubscriptionConsumer($this->createAmqpContextMock());
    }

    /**
     * @return AmqpContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createAmqpContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }
}
