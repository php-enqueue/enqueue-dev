<?php

namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompMessage;
use Enqueue\Test\RabbitManagementExtensionTrait;
use Enqueue\Test\RabbitmqStompExtension;

/**
 * @group functional
 */
class StompCommonUseCasesTest extends \PHPUnit\Framework\TestCase
{
    use RabbitManagementExtensionTrait;
    use RabbitmqStompExtension;

    /**
     * @var StompContext
     */
    private $stompContext;

    protected function setUp(): void
    {
        $this->stompContext = $this->buildStompContext();

        $this->removeQueue('stomp.test');
    }

    protected function tearDown(): void
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
        $message = $consumer->receive(2000);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(3, $endAt - $startAt);
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

        $this->assertLessThan(1, $endAt - $startAt);
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
        $message = $consumer->receive(1000);

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
