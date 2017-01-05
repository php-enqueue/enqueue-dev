<?php

namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompMessage;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;
use Enqueue\Test\RabbitmqStompExtension;

/**
 * @group functional
 */
class StompCommonUseCasesTest extends \PHPUnit_Framework_TestCase
{
    use RabbitmqStompExtension;
    use RabbitmqManagmentExtensionTrait;

    /**
     * @var StompContext
     */
    private $stompContext;

    public function setUp()
    {
        $this->stompContext = $this->buildStompContext();

        $this->removeQueue('stomp.test');
    }

    public function tearDown()
    {
        $this->stompContext->close();
    }

    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->stompContext->createQueue('stomp.test');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        $startAt = microtime(true);

        $consumer = $this->stompContext->createConsumer($queue);
        $message = $consumer->receive(2);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(2.5, $endAt - $startAt);
    }

    public function testReturnNullImmediatelyOnReceiveNoWait()
    {
        $queue = $this->stompContext->createQueue('stomp.test');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        $startAt = microtime(true);

        $consumer = $this->stompContext->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(0.5, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessage()
    {
        $queue = $this->stompContext->createQueue('stomp.test');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        $message = $this->stompContext->createMessage(
            __METHOD__,
            ['FooProperty' => 'FooVal'],
            ['BarHeader' => 'BarVal']
        );

        $producer = $this->stompContext->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->stompContext->createConsumer($queue);
        $message = $consumer->receive(1);

        $this->assertInstanceOf(StompMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
        $this->assertEquals(['FooProperty' => 'FooVal'], $message->getProperties());
        $this->assertEquals([
            'exclusive' => false,
            'auto-delete' => false,
            'durable' => true,
            'BarHeader' => 'BarVal',
        ], $message->getHeaders());
    }
}
