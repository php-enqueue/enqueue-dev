<?php
namespace Enqueue\Sqs\Tests\Functional;

use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Test\SqsExtension;
use PHPUnit\Framework\TestCase;

class SqsCommonUseCasesTest extends TestCase
{
    use SqsExtension;

    /**
     * @var SqsContext
     */
    private $context;

    /**
     * @var SqsDestination
     */
    private $queue;

    /**
     * @var string
     */
    private $queueName;

    protected function setUp()
    {
        parent::setUp();

        $this->context = $this->buildSqsContext();

        $this->queueName = str_replace('.', '_dot_', uniqid('enqueue_test_queue_', true));;
        $this->queue = $this->context->createQueue($this->queueName);

        $this->context->declareQueue($this->queue);
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->context && $this->queue) {
            $this->context->deleteQueue($this->queue);
        }
    }

    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->context->createQueue($this->queueName);

        $startAt = microtime(true);

        $consumer = $this->context->createConsumer($queue);
        $message = $consumer->receive(2000);

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertGreaterThan(1.5, $endAt - $startAt);
        $this->assertLessThan(2.5, $endAt - $startAt);
    }

    public function testReturnNullImmediatelyOnReceiveNoWait()
    {
        $queue = $this->context->createQueue($this->queueName);

        $startAt = microtime(true);

        $consumer = $this->context->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(2, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToQueue()
    {
        $queue = $this->context->createQueue($this->queueName);

        $message = $this->context->createMessage(
            __METHOD__,
            ['FooProperty' => 'FooVal'],
            ['BarHeader' => 'BarVal']
        );

        $producer = $this->context->createProducer();
        $producer->send($queue, $message);

        $consumer = $this->context->createConsumer($queue);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(SqsMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
        $this->assertEquals(['FooProperty' => 'FooVal'], $message->getProperties());
        $this->assertEquals(['BarHeader' => 'BarVal'], $message->getHeaders());
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToTopic()
    {
        $topic = $this->context->createTopic($this->queueName);

        $message = $this->context->createMessage(__METHOD__);

        $producer = $this->context->createProducer();
        $producer->send($topic, $message);

        $consumer = $this->context->createConsumer($topic);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(SqsMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }
}
