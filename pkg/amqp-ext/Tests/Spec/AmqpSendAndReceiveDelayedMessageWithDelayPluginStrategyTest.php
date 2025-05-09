<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendAndReceiveDelayedMessageFromQueueSpec;

/**
 * @group functional
 */
class AmqpSendAndReceiveDelayedMessageWithDelayPluginStrategyTest extends SendAndReceiveDelayedMessageFromQueueSpec
{
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));
        $factory->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy());

        return $factory->createContext();
    }

    /**
     * @param AmqpContext $context
     */
    protected function createQueue(Context $context, $queueName)
    {
        $queue = parent::createQueue($context, $queueName);

        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
