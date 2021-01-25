<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisSubscriptionConsumer;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;

class RedisSubscriptionConsumerTest extends TestCase
{
    use ReadAttributeTrait;

    public function testShouldImplementSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(RedisSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(SubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithRedisContextAsFirstArgument()
    {
        new RedisSubscriptionConsumer($this->createRedisContextMock());
    }

    public function testShouldAddConsumerAndCallbackToSubscribersPropertyOnSubscribe()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $barCallback = function () {};
        $barConsumer = $this->createConsumerStub('bar_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
        $subscriptionConsumer->subscribe($barConsumer, $barCallback);

        $this->assertAttributeSame([
            'foo_queue' => [$fooConsumer, $fooCallback],
            'bar_queue' => [$barConsumer, $barCallback],
        ], 'subscribers', $subscriptionConsumer);
    }

    public function testThrowsIfTrySubscribeAnotherConsumerToAlreadySubscribedQueue()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $barCallback = function () {};
        $barConsumer = $this->createConsumerStub('foo_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is a consumer subscribed to queue: "foo_queue"');
        $subscriptionConsumer->subscribe($barConsumer, $barCallback);
    }

    public function testShouldAllowSubscribeSameConsumerAndCallbackSecondTime()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
    }

    public function testShouldRemoveSubscribedConsumerOnUnsubscribeCall()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $fooConsumer = $this->createConsumerStub('foo_queue');
        $barConsumer = $this->createConsumerStub('bar_queue');

        $subscriptionConsumer->subscribe($fooConsumer, function () {});
        $subscriptionConsumer->subscribe($barConsumer, function () {});

        // guard
        $this->assertAttributeCount(2, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($fooConsumer);

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldDoNothingIfTryUnsubscribeNotSubscribedQueueName()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('bar_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldDoNothingIfTryUnsubscribeNotSubscribedConsumer()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('foo_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldRemoveAllSubscriberOnUnsubscribeAllCall()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});
        $subscriptionConsumer->subscribe($this->createConsumerStub('bar_queue'), function () {});

        // guard
        $this->assertAttributeCount(2, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribeAll();

        $this->assertAttributeCount(0, 'subscribers', $subscriptionConsumer);
    }

    public function testThrowsIfTryConsumeWithoutSubscribers()
    {
        $subscriptionConsumer = new RedisSubscriptionConsumer($this->createRedisContextMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No subscribers');
        $subscriptionConsumer->consume();
    }

    /**
     * @return RedisContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRedisContextMock()
    {
        return $this->createMock(RedisContext::class);
    }

    /**
     * @param mixed|null $queueName
     *
     * @return Consumer|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createConsumerStub($queueName = null)
    {
        $queueMock = $this->createMock(Queue::class);
        $queueMock
            ->expects($this->any())
            ->method('getQueueName')
            ->willReturn($queueName);

        $consumerMock = $this->createMock(RedisConsumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queueMock)
        ;

        return $consumerMock;
    }
}
