<?php
namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Psr\Context;
use Enqueue\Psr\Message;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;
use Enqueue\Test\RabbitmqStompExtension;
use Enqueue\Stomp\StompContext;

/**
 * @group functional
 */
class StompConsumptionUseCasesTest extends \PHPUnit_Framework_TestCase
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

    public function testConsumeOneMessageAndExit()
    {
        $queue = $this->stompContext->createQueue('stomp.test');

        $message = $this->stompContext->createMessage(__METHOD__);
        $this->stompContext->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->stompContext, new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
        ]));

        $processor = new StubMessageProcessor();
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

        $processor = new StubMessageProcessor();
        $processor->result = Result::reply($replyMessage);

        $replyProcessor = new StubMessageProcessor();

        $queueConsumer->bind($queue, $processor);
        $queueConsumer->bind($replyQueue, $replyProcessor);
        $queueConsumer->consume();

        $this->assertInstanceOf(Message::class, $processor->lastProcessedMessage);
        $this->assertEquals(__METHOD__, $processor->lastProcessedMessage->getBody());

        $this->assertInstanceOf(Message::class, $replyProcessor->lastProcessedMessage);
        $this->assertEquals(__METHOD__.'.reply', $replyProcessor->lastProcessedMessage->getBody());
    }
}

class StubMessageProcessor implements MessageProcessorInterface
{
    public $result = Result::ACK;

    /** @var Message */
    public $lastProcessedMessage;

    public function process(Message $message, Context $context)
    {
        $this->lastProcessedMessage = $message;

        return $this->result;
    }
}
