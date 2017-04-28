<?php
namespace Enqueue\Sqs\Tests\Functional;

use Enqueue\Sqs\SqsContext;
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

    protected function setUp()
    {
        parent::setUp();

        $this->context = $this->buildSqsContext();

        $queue = $this->context->createQueue('enqueue_test_queue');
        $this->context->declareQueue($queue);

        try {
            $this->context->purge($queue);
        } catch (\Exception $e) {}
    }

    public function testWaitsForTwoSecondsAndReturnNullOnReceive()
    {
        $queue = $this->context->createQueue('enqueue_test_queue');

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
        $queue = $this->context->createQueue('enqueue_test_queue');

        $startAt = microtime(true);

        $consumer = $this->context->createConsumer($queue);
        $message = $consumer->receiveNoWait();

        $endAt = microtime(true);

        $this->assertNull($message);

        $this->assertLessThan(2, $endAt - $startAt);
    }

    public function testProduceAndReceiveOneMessageSentDirectlyToQueue()
    {
        $queue = $this->context->createQueue('enqueue_test_queue');

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
        $topic = $this->context->createTopic('enqueue_test_queue');

        $message = $this->context->createMessage(__METHOD__);

        $producer = $this->context->createProducer();
        $producer->send($topic, $message);

        $consumer = $this->context->createConsumer($topic);
        $message = $consumer->receive(1000);

        $this->assertInstanceOf(SqsMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertEquals(__METHOD__, $message->getBody());
    }

    public function testConsumerReceiveMessageWithZeroTimeout()
    {
        $topic = $this->context->createTopic('enqueue_test_queue');

        $consumer = $this->context->createConsumer($topic);

        //guard
        $this->assertNull($consumer->receive(1000));

        $message = $this->context->createMessage(__METHOD__);

        $producer = $this->context->createProducer();
        $producer->send($topic, $message);
        usleep(1000);
        $actualMessage = $consumer->receive(0);

        $this->assertInstanceOf(SqsMessage::class, $actualMessage);
        $consumer->acknowledge($actualMessage);

        $this->assertEquals(__METHOD__, $message->getBody());
    }
}
