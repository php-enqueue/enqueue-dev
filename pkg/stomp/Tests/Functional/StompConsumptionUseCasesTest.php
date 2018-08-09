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
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

/**
 * @group functional
 */
class StompConsumptionUseCasesTest extends \PHPUnit\Framework\TestCase
{
    use RabbitmqStompExtension;
    use RabbitManagementExtensionTrait;

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

        $this->assertInstanceOf(PsrMessage::class, $processor->lastProcessedMessage);
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
