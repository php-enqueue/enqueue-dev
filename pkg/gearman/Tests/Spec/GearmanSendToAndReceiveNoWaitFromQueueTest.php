<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class GearmanSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new GearmanConnectionFactory(getenv('GEARMAN_DSN'));

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
