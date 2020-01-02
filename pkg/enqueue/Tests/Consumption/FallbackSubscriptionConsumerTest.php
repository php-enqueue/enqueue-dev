<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\FallbackSubscriptionConsumer;
use Interop\Queue\Consumer;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;

class FallbackSubscriptionConsumerTest extends TestCase
{
    public function testShouldImplementSubscriptionConsumerInterface()
    {
        $rc = new \ReflectionClass(FallbackSubscriptionConsumer::class);

        $this->assertTrue($rc->implementsInterface(SubscriptionConsumer::class));
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new FallbackSubscriptionConsumer();
    }

    public function testShouldInitSubscribersPropertyWithEmptyArray()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $this->assertAttributeSame([], 'subscribers', $subscriptionConsumer);
    }

    public function testShouldAddConsumerAndCallbackToSubscribersPropertyOnSubscribe()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

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
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

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
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $fooCallback = function () {};
        $fooConsumer = $this->createConsumerStub('foo_queue');

        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
        $subscriptionConsumer->subscribe($fooConsumer, $fooCallback);
    }

    public function testShouldRemoveSubscribedConsumerOnUnsubscribeCall()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

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
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('bar_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldDoNothingIfTryUnsubscribeNotSubscribedConsumer()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});

        // guard
        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribe($this->createConsumerStub('foo_queue'));

        $this->assertAttributeCount(1, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldRemoveAllSubscriberOnUnsubscribeAllCall()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $subscriptionConsumer->subscribe($this->createConsumerStub('foo_queue'), function () {});
        $subscriptionConsumer->subscribe($this->createConsumerStub('bar_queue'), function () {});

        // guard
        $this->assertAttributeCount(2, 'subscribers', $subscriptionConsumer);

        $subscriptionConsumer->unsubscribeAll();

        $this->assertAttributeCount(0, 'subscribers', $subscriptionConsumer);
    }

    public function testShouldConsumeMessagesFromTwoQueuesInExpectedOrder()
    {
        $firstMessage = $this->createMessageStub('first');
        $secondMessage = $this->createMessageStub('second');
        $thirdMessage = $this->createMessageStub('third');
        $fourthMessage = $this->createMessageStub('fourth');
        $fifthMessage = $this->createMessageStub('fifth');

        $fooMessages = [null, $firstMessage, null, $secondMessage, $thirdMessage];

        $fooConsumer = $this->createConsumerStub('foo_queue');
        $fooConsumer
            ->expects($this->any())
            ->method('receiveNoWait')
            ->willReturnCallback(function () use (&$fooMessages) {
                if (empty($fooMessages)) {
                    return null;
                }

                return array_shift($fooMessages);
            })
        ;

        $barMessages = [$fourthMessage, null, null, $fifthMessage];

        $barConsumer = $this->createConsumerStub('bar_queue');
        $barConsumer
            ->expects($this->any())
            ->method('receiveNoWait')
            ->willReturnCallback(function () use (&$barMessages) {
                if (empty($barMessages)) {
                    return null;
                }

                return array_shift($barMessages);
            })
        ;

        $actualOrder = [];
        $callback = function (InteropMessage $message, Consumer $consumer) use (&$actualOrder) {
            $actualOrder[] = [$message->getBody(), $consumer->getQueue()->getQueueName()];
        };

        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $subscriptionConsumer->subscribe($fooConsumer, $callback);
        $subscriptionConsumer->subscribe($barConsumer, $callback);

        $subscriptionConsumer->consume(100);

        $this->assertEquals([
            ['fourth', 'bar_queue'],
            ['first', 'foo_queue'],
            ['second', 'foo_queue'],
            ['fifth', 'bar_queue'],
            ['third', 'foo_queue'],
        ], $actualOrder);
    }

    public function testThrowsIfTryConsumeWithoutSubscribers()
    {
        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No subscribers');
        $subscriptionConsumer->consume();
    }

    public function testShouldConsumeTillTimeoutIsReached()
    {
        $fooConsumer = $this->createConsumerStub('foo_queue');
        $fooConsumer
            ->expects($this->any())
            ->method('receiveNoWait')
            ->willReturn(null)
        ;

        $subscriptionConsumer = new FallbackSubscriptionConsumer();

        $subscriptionConsumer->subscribe($fooConsumer, function () {});

        $startAt = microtime(true);
        $subscriptionConsumer->consume(500);
        $endAt = microtime(true);

        $this->assertGreaterThan(0.49, $endAt - $startAt);
    }

    /**
     * @param mixed|null $body
     *
     * @return InteropMessage|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMessageStub($body = null)
    {
        $messageMock = $this->createMock(InteropMessage::class);
        $messageMock
            ->expects($this->any())
            ->method('getBody')
            ->willReturn($body)
        ;

        return $messageMock;
    }

    /**
     * @param mixed|null $queueName
     *
     * @return Consumer|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createConsumerStub($queueName = null)
    {
        $queueMock = $this->createMock(InteropQueue::class);
        $queueMock
            ->expects($this->any())
            ->method('getQueueName')
            ->willReturn($queueName);

        $consumerMock = $this->createMock(Consumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queueMock)
        ;

        return $consumerMock;
    }
}
