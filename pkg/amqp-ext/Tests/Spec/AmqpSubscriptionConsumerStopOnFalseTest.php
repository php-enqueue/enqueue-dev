<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\Context;
use Interop\Queue\Spec\SubscriptionConsumerStopOnFalseSpec;

/**
 * @group functional
 */
class AmqpSubscriptionConsumerStopOnFalseTest extends SubscriptionConsumerStopOnFalseSpec
{
    /**
     * @return AmqpContext
     *
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        $context = $factory->createContext();
        $context->setQos(0, 5, false);

        return $context;
    }

    /**
     * @param AmqpContext $context
     *
     * {@inheritdoc}
     */
    protected function createQueue(Context $context, $queueName)
    {
        /** @var AmqpQueue $queue */
        $queue = parent::createQueue($context, $queueName);
        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
