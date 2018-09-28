<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Queue;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

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
     * @param Context $context
     * @param string  $queueName
     *
     * @return Queue
     */
    protected function createQueue(Context $context, $queueName)
    {
        return $context->createQueue($queueName.time());
    }
}
