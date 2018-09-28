<?php

namespace Enqueue\Redis\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Redis\RedisContext;
use Interop\Queue\Message;

trait ConsumptionUseCasesTrait
{
    public function testConsumeOneMessageAndExit()
    {
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $message = $this->getContext()->createMessage(__METHOD__);
        $this->getContext()->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->getContext(), new ChainExtension([
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
        $queue = $this->getContext()->createQueue('enqueue.test_queue');

        $replyQueue = $this->getContext()->createQueue('enqueue.test_queue_reply');

        $message = $this->getContext()->createMessage(__METHOD__);
        $message->setReplyTo($replyQueue->getQueueName());
        $this->getContext()->createProducer()->send($queue, $message);

        $queueConsumer = new QueueConsumer($this->getContext(), new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('+3sec')),
            new ReplyExtension(),
        ]));

        $replyMessage = $this->getContext()->createMessage(__METHOD__.'.reply');

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

    /**
     * @return RedisContext
     */
    abstract protected function getContext();
}
