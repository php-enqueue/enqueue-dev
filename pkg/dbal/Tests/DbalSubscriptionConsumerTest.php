<?php

declare(strict_types=1);

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConsumer;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalSubscriptionConsumer;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbalSubscriptionConsumerTest extends TestCase
{
    use ReadAttributeTrait;

    public function testShouldImplementSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(DbalSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(SubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithDbalContextAsFirstArgument()
    {
        new DbalSubscriptionConsumer($this->createDbalContextMock());
    }

    public function testShouldAddConsumerAndCallbackToSubscribersPropertyOnSubscribe()
    {
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

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
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

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
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
    }

    public function testShouldRemoveSubscribedConsumerOnUnsubscribeCall()
    {
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

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
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('bar_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldDoNothingIfTryUnsubscribeNotSubscribedConsumer()
    {
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('foo_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldRemoveAllSubscriberOnUnsubscribeAllCall()
    {
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});
        $subscriptionConsumer->subscribe($this->createConsumerStub('bar_queue'), function () {});

        // guard
        $this->assertAttributeCount(2, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribeAll();

        $this->assertAttributeCount(0, 'subscribers', $subscriptionConsumer);
    }

    public function testThrowsIfTryConsumeWithoutSubscribers()
    {
        $subscriptionConsumer = new DbalSubscriptionConsumer($this->createDbalContextMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No subscribers');

        $subscriptionConsumer->consume();
    }

    /**
     * @return DbalContext|MockObject
     */
    private function createDbalContextMock()
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @param mixed|null $queueName
     *
     * @return Consumer|MockObject
     */
    private function createConsumerStub($queueName = null)
    {
        $queueMock = $this->createMock(Queue::class);
        $queueMock
            ->expects($this->any())
            ->method('getQueueName')
            ->willReturn($queueName);

        $consumerMock = $this->createMock(DbalConsumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queueMock);

        return $consumerMock;
    }
}
