<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\SubscriptionConsumer;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrSubscriptionConsumer;

class SubscriptionConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldImplementPsrSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(SubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(PsrSubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithAmqpContextAsFirstArgument()
    {
        new SubscriptionConsumer($this->createContext());
    }

    public function testShouldProxySubscribeCallToContextMethod()
    {
        $consumer = $this->createConsumer();
        $callback = function () {};

        $context = $this->createContext();
        $context
            ->expects($this->once())
            ->method('subscribe')
            ->with($this->identicalTo($consumer), $this->identicalTo($callback))
        ;

        $subscriptionConsumer = new SubscriptionConsumer($context);
        $subscriptionConsumer->subscribe($consumer, $callback);
    }

    public function testShouldProxyUnsubscribeCallToContextMethod()
    {
        $consumer = $this->createConsumer();

        $context = $this->createContext();
        $context
            ->expects($this->once())
            ->method('unsubscribe')
            ->with($this->identicalTo($consumer))
        ;

        $subscriptionConsumer = new SubscriptionConsumer($context);
        $subscriptionConsumer->unsubscribe($consumer);
    }

    public function testShouldProxyConsumeCallToContextMethod()
    {
        $timeout = 123.456;

        $context = $this->createContext();
        $context
            ->expects($this->once())
            ->method('consume')
            ->with($this->identicalTo($timeout))
        ;

        $subscriptionConsumer = new SubscriptionConsumer($context);
        $subscriptionConsumer->consume($timeout);
    }

    public function testThrowsNotImplementedOnUnsubscribeAllCall()
    {
        $context = $this->createContext();

        $subscriptionConsumer = new SubscriptionConsumer($context);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');
        $subscriptionConsumer->unsubscribeAll();
    }

    /**
     * @return AmqpConsumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createConsumer()
    {
        return $this->createMock(AmqpConsumer::class);
    }

    /**
     * @return AmqpContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContext()
    {
        return $this->createMock(AmqpContext::class);
    }
}
