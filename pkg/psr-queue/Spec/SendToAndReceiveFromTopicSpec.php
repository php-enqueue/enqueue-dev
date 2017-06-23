<?php

namespace Enqueue\Psr\Spec;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrTopic;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
abstract class SendToAndReceiveFromTopicSpec extends TestCase
{
    public function test()
    {
        $context = $this->createContext();
        $topic = $this->createTopic($context, 'send_to_and_receive_from_topic_spec');

        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));
//        $context->close();
//
//        $context = $this->createContext();
        $consumer = $context->createConsumer($topic);

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
     * @param string     $topicName
     *
     * @return PsrTopic
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        return $context->createTopic($topicName);
    }
}
