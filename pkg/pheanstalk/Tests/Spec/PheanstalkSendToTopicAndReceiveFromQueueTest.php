<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class PheanstalkSendToTopicAndReceiveFromQueueTest extends SendToTopicAndReceiveFromQueueSpec
{
    private $time;

    public function setUp()
    {
        $this->time = time();
    }

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
        return $context->createQueue($queueName.$this->time);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        return $context->createTopic($topicName.$this->time);
    }
}
