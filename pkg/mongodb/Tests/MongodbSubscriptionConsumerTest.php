<?php

declare(strict_types=1);

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbConsumer;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbSubscriptionConsumer;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;

class MongodbSubscriptionConsumerTest extends TestCase
{
    use ReadAttributeTrait;

    public function testShouldImplementSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(MongodbSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(SubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithMongodbContextAsFirstArgument()
    {
        new MongodbSubscriptionConsumer($this->createMongodbContextMock());
    }

    public function testShouldAddConsumerAndCallbackToSubscribersPropertyOnSubscribe()
    {
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

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
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

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
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
    }

    public function testShouldRemoveSubscribedConsumerOnUnsubscribeCall()
    {
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

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
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('bar_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldDoNothingIfTryUnsubscribeNotSubscribedConsumer()
    {
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('foo_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldRemoveAllSubscriberOnUnsubscribeAllCall()
    {
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});
        $subscriptionConsumer->subscribe($this->createConsumerStub('bar_queue'), function () {});

        // guard
        $this->assertAttributeCount(2, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribeAll();

        $this->assertAttributeCount(0, 'subscribers', $subscriptionConsumer);
    }

    public function testThrowsIfTryConsumeWithoutSubscribers()
    {
        $subscriptionConsumer = new MongodbSubscriptionConsumer($this->createMongodbContextMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No subscribers');

        $subscriptionConsumer->consume();
    }

    /**
     * @return MongodbContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMongodbContextMock()
    {
        return $this->createMock(MongodbContext::class);
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

        $consumerMock = $this->createMock(MongodbConsumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queueMock)
        ;

        return $consumerMock;
    }
}
