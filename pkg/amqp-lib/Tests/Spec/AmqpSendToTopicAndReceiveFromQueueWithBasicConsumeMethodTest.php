<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\PsrMessage;
use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class AmqpSendToTopicAndReceiveFromQueueWithBasicConsumeMethodTest extends SendToTopicAndReceiveFromQueueSpec
{
    private $topic;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN').'?receive_method=basic_consume');

        return $factory->createContext();
    }

    public function test()
    {
        $context = $this->createContext();

        $topic = $context->createTopic('send_to_topic_and_receive_from_queue_spec_basic_consume');
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        try {
            $context->deleteTopic($topic);
        } catch (\Exception $e) {}

        $context->declareTopic($topic);

        $queue = $context->createQueue('send_to_topic_and_receive_from_queue_spec_basic_consume');

        try {
            $context->deleteQueue($queue);
        } catch (\Exception $e) {}

        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        $context->bind(new AmqpBind($topic, $queue));

        $consumer = $context->createConsumer($queue);

         // guard
        $this->assertNull($consumer->receiveNoWait());

        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));

        $message = $consumer->receive(2000); // 2 sec

        $this->assertInstanceOf(PsrMessage::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }
}
