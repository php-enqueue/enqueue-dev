<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Makasim\File\TempFile;

/**
 * @group functional
 */
class FsConsumptionUseCasesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    protected function setUp(): void
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        new TempFile(sys_get_temp_dir().'/fs_test_queue');
    }

    protected function tearDown(): void
    {
        $this->fsContext->close();
    }

    public function testConsumeOneMessageAndExit()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $message = $this->fsContext->createMessage(__METHOD__);
        $this->fsContext->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->fsContext, new ChainExtension([
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
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $replyQueue = $this->fsContext->createQueue('fs_test_queue_reply');

        $message = $this->fsContext->createMessage(__METHOD__);
        $message->setReplyTo($replyQueue->getQueueName());
        $this->fsContext->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->fsContext, new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
            new ReplyExtension(),
        ]));

        $replyMessage = $this->fsContext->createMessage(__METHOD__.'.reply');

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
