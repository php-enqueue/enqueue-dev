<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class PheanstalkSendToTopicAndReceiveFromQueueTest extends SendToTopicAndReceiveFromQueueSpec
{
    private $time;

    protected function setUp(): void
    {
        $this->time = time();
    }

    protected function createContext()
    {
        $factory = new PheanstalkConnectionFactory(getenv('BEANSTALKD_DSN'));

        return $factory->createContext();
    }

    protected function createQueue(Context $context, $queueName)
    {
        return $context->createQueue($queueName.$this->time);
    }

    protected function createTopic(Context $context, $topicName)
    {
        return $context->createTopic($topicName.$this->time);
    }
}
