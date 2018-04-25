<?php

namespace Enqueue\Sqs\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Sqs\SqsContext;
use Enqueue\Test\RetryTrait;
use Enqueue\Test\SqsExtension;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;

class SqsConsumptionUseCasesTest extends TestCase
{
    use SqsExtension;
    use RetryTrait;

    /**
     * @var SqsContext
     */
    private $context;

    protected function setUp()
    {
        parent::setUp();

        $this->context = $this->buildSqsContext();

        $queue = $this->context->createQueue('enqueue_test_queue');
        $replyQueue = $this->context->createQueue('enqueue_test_queue_reply');

        $this->context->declareQueue($queue);
        $this->context->declareQueue($replyQueue);

        try {
            $this->context->purge($queue);
            $this->context->purge($replyQueue);
        } catch (\Exception $e) {
        }
    }

    /**
     * @retry 5
     */
    public function testConsumeOneMessageAndExit()
    {
        $queue = $this->context->createQueue('enqueue_test_queue');

        $message = $this->context->createMessage(__METHOD__);
        $this->context->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
        ]));

        $processor = new StubProcessor();
        $queueConsumer->bind($queue, $processor);

        $queueConsumer->consume();

        $this->assertInstanceOf(PsrMessage::class, $processor->lastProcessedMessage);
        $this->assertEquals(__METHOD__, $processor->lastProcessedMessage->getBody());
    }

    /**
     * @retry 5
     */
    public function testConsumeOneMessageAndSendReplyExit()
    {
        $queue = $this->context->createQueue('enqueue_test_queue');
        $replyQueue = $this->context->createQueue('enqueue_test_queue_reply');

        $message = $this->context->createMessage(__METHOD__);
        $message->setReplyTo($replyQueue->getQueueName());
        $this->context->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
            new ReplyExtension(),
        ]));

        $replyMessage = $this->context->createMessage(__METHOD__.'.reply');

        $processor = new StubProcessor();
        $processor->result = Result::reply($replyMessage);

        $replyProcessor = new StubProcessor();

        $queueConsumer->bind($queue, $processor);
        $queueConsumer->bind($replyQueue, $replyProcessor);
        $queueConsumer->consume();

        $this->assertInstanceOf(PsrMessage::class, $processor->lastProcessedMessage);
        $this->assertEquals(__METHOD__, $processor->lastProcessedMessage->getBody());

        $this->assertInstanceOf(PsrMessage::class, $replyProcessor->lastProcessedMessage);
        $this->assertEquals(__METHOD__.'.reply', $replyProcessor->lastProcessedMessage->getBody());
    }
}

class StubProcessor implements PsrProcessor
{
    public $result = self::ACK;

    /** @var PsrMessage */
    public $lastProcessedMessage;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->lastProcessedMessage = $message;

        return $this->result;
    }
}
