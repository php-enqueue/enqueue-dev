<?php

namespace Enqueue\Redis\Tests\Functional;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisMessage;

trait CommonUseCasesTrait
{
    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $startAt = microtime(true);

        $consumer = $this->getContext()->createConsumer($queue);
        $message = $consumer->receive(2000);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(2.5, $endAt - $startAt);
    }

    public function testReturnNullImmediatelyOnReceiveNoWait()
    {
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $startAt = microtime(true);

        $consumer = $this->getContext()->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(0.5, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToQueue()
    {
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $message = $this->getContext()->createMessage(
            __METHOD__,
            ['FooProperty' => 'FooVal'],
            ['BarHeader' => 'BarVal']
        );

        $producer = $this->getContext()->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->getContext()->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(RedisMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
        $this->assertEquals(['FooProperty' => 'FooVal'], $message->getProperties());
        $this->assertEquals(['BarHeader' => 'BarVal'], $message->getHeaders());
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToTopic()
    {
        $topic = $this->getContext()->createTopic('enqueue.test_topic');

        $message = $this->getContext()->createMessage(__METHOD__);

        $producer = $this->getContext()->createProducer();
        $producer->send($topic, $message);

        $consumer = $this->getContext()->createConsumer($topic);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(RedisMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testConsumerReceiveMessageWithZeroTimeout()
    {
        $topic = $this->getContext()->createTopic('enqueue.test_topic');

        $consumer = $this->getContext()->createConsumer($topic);

        //guard
        $this->assertNull($consumer->receive(1000));

        $message = $this->getContext()->createMessage(__METHOD__);

        $producer = $this->getContext()->createProducer();
        $producer->send($topic, $message);
        usleep(100);
        $actualMessage = $consumer->receive(0);

        $this->assertInstanceOf(RedisMessage::class, $actualMessage);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testShouldReceiveMessagesInExpectedOrder()
    {
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $producer = $this->getContext()->createProducer();
        $producer->send($queue, $this->getContext()->createMessage('1'));
        $producer->send($queue, $this->getContext()->createMessage('2'));
        $producer->send($queue, $this->getContext()->createMessage('3'));

        $consumer = $this->getContext()->createConsumer($queue);

        $this->assertSame('1', $consumer->receiveNoWait()->getBody());
        $this->assertSame('2', $consumer->receiveNoWait()->getBody());
        $this->assertSame('3', $consumer->receiveNoWait()->getBody());
    }

    /**
     * @return RedisContext
     */
    abstract protected function getContext();
}
