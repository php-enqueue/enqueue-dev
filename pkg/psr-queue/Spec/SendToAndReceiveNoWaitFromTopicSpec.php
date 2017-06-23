<?php

namespace Enqueue\Psr\Spec;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrTopic;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
abstract class SendToAndReceiveNoWaitFromTopicSpec extends TestCase
{
    public function test()
    {
        $context = $this->createContext();
        $topic = $this->createTopic($context, 'send_to_and_receive_no_wait_from_topic_spec');

        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));
//        $context->close();
//
//        $context = $this->createContext();
        $consumer = $context->createConsumer($topic);

        $startTime = microtime(true);
        $message = $consumer->receiveNoWait();

        $this->assertLessThan(2, microtime(true) - $startTime);

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
     * @param string     $topicName
     *
     * @return PsrTopic
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        return $context->createTopic($topicName);
    }
}
