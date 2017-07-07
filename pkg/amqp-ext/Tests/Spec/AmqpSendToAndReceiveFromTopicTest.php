<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class AmqpSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpContext $context
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        $topic = $context->createTopic($topicName);
        $topic->setType(\AMQP_EX_TYPE_FANOUT);
        $topic->addFlag(\AMQP_DURABLE);
        $context->declareTopic($topic);

        return $topic;
    }
}
