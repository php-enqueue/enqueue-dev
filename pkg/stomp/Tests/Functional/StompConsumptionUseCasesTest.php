<?php

namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Stomp\StompContext;
use Enqueue\Test\RabbitManagementExtensionTrait;
use Enqueue\Test\RabbitmqStompExtension;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

/**
 * @group functional
 */
class StompConsumptionUseCasesTest extends \PHPUnit\Framework\TestCase
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

    public function testConsumeOneMessageAndExit()
    {
        $queue = $this->stompContext->createQueue('stomp.test');

        $message = $this->stompContext->createMessage(__METHOD__);
        $this->stompContext->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->stompContext, new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
        ]));

        $processor = new StubProcessor();
        $queueConsumer->bind($queue, $processor);

        $queueConsumer->consume();

        $this->assertInstanceOf(Message::class, $processor->lastProcessedMessage);
        $this->assertEquals(__METHOD__, $processor->lastProcessedMessage->getBody());
    }

    public function testConsumeOneMessageAndSendReplyExit()
    {
        $queue = $this->stompContext->createQueue('stomp.test');

        $replyQueue = $this->stompContext->createQueue('stomp.test_reply');

        $message = $this->stompContext->createMessage(__METHOD__);
        $message->setReplyTo($replyQueue->getQueueName());
        $this->stompContext->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->stompContext, new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
            new ReplyExtension(),
        ]));

        $replyMessage = $this->stompContext->createMessage(__METHOD__.'.reply');

        $processor = new StubProcessor();
        $processor->result = Result::reply($replyMessage);

        $replyProcessor = new StubProcessor();

        $queueConsumer->bind($queue, $processor);
        $queueConsumer->bind($replyQueue, $replyProcessor);
        $queueConsumer->consume();

        $this->assertInstanceOf(Message::class, $processor->lastProcessedMessage);
        $this->assertEquals(__METHOD__, $processor->lastProcessedMessage->getBody());

        $this->assertInstanceOf(Message::class, $replyProcessor->lastProcessedMessage);
        $this->assertEquals(__METHOD__.'.reply', $replyProcessor->lastProcessedMessage->getBody());
    }
}

class StubProcessor implements Processor
{
    public $result = self::ACK;

    /** @var Message */
    public $lastProcessedMessage;

    public function process(Message $message, Context $context)
    {
        $this->lastProcessedMessage = $message;

        return $this->result;
    }
}
