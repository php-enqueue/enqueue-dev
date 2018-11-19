<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendAndReceivePriorityMessagesFromQueueSpec;

/**
 * @group functional
 */
class AmqpSendAndReceivePriorityMessagesFromQueueTest extends SendAndReceivePriorityMessagesFromQueueSpec
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
    protected function createQueue(Context $context, $queueName)
    {
        $queue = $context->createQueue($queueName);
        $queue->setArguments(['x-max-priority' => 10]);

        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
