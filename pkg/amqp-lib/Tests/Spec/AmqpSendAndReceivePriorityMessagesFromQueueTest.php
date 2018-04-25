<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Queue\PsrContext;
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
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queue = $context->createQueue($queueName);
        $queue->setArguments(['x-max-priority' => 10]);

        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
