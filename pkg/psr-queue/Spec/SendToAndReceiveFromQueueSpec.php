<?php

namespace Enqueue\Psr\Spec;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrQueue;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
abstract class SendToAndReceiveFromQueueSpec extends TestCase
{
    public function test()
    {
        $context = $this->createContext();
        $queue = $this->createQueue($context, 'send_to_and_receive_from_queue_spec');

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertNull($consumer->receiveNoWait());

        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($queue, $context->createMessage($expectedBody));

        $message = $consumer->receive(2000); // 2 sec

        $this->assertInstanceOf(PsrMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    /**
     * @return PsrContext
     */
    abstract protected function createContext();

    /**
     * @param PsrContext $context
     * @param string     $queueName
     *
     * @return PsrQueue
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        return $context->createQueue($queueName);
    }
}
