<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\PsrContext;
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

    /**
     * {@inheritdoc}
     *
     * @param AmqpContext $context
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queue = $context->createQueue('send_to_topic_and_receive_from_queue_spec_basic_consume');

        try {
            $context->deleteQueue($queue);
        } catch (\Exception $e) {
        }

        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        $context->bind(new AmqpBind($this->topic, $queue));

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpContext $context
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        $topic = $context->createTopic('send_to_topic_and_receive_from_queue_spec_basic_consume');
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        try {
            $context->deleteTopic($topic);
        } catch (\Exception $e) {
        }

        $context->declareTopic($topic);

        return $this->topic = $topic;
    }
}
