<?php

namespace Enqueue\Pheanstalk;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class PheanstalkSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new PheanstalkConnectionFactory(getenv('BEANSTALKD_DSN'));

        return $factory->createContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        return $context->createQueue($queueName.time());
    }
}
