<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class PheanstalkSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
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
     * @param PsrContext $context
     * @param string     $queueName
     *
     * @return PsrQueue
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        return $context->createQueue($queueName.time());
    }
}
