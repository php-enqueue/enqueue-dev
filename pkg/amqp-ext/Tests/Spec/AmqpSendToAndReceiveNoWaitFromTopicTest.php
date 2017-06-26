<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 */
class AmqpSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
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
