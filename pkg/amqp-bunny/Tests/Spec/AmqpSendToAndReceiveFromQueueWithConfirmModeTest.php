<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Enqueue\AmqpBunny\AmqpContext;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class AmqpSendToAndReceiveFromQueueWithConfirmModeTest extends SendToAndReceiveFromQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new AmqpConnectionFactory(['dsn' => getenv('AMQP_DSN'), 'publisher_confirm_timeout' => 3]);

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
        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
