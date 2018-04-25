<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class GearmanSendToTopicAndReceiveFromQueueTest extends SendToTopicAndReceiveFromQueueSpec
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
        $factory = new GearmanConnectionFactory(getenv('GEARMAN_DSN'));

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
