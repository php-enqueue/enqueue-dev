<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 * @group gearman
 */
class GearmanSendToTopicAndReceiveNoWaitFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
{
    private $time;

    protected function setUp(): void
    {
        $this->time = time();
    }

    protected function createContext()
    {
        $factory = new GearmanConnectionFactory(getenv('GEARMAN_DSN'));

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
