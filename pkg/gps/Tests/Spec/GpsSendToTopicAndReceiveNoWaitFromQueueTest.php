<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsContext;
use Enqueue\Test\GpsExtension;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class GpsSendToTopicAndReceiveNoWaitFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
{
    use GpsExtension;

    private $topic;

    protected function createContext()
    {
        return $this->buildGpsContext();
    }

    /**
     * @param GpsContext $context
     * @param mixed      $queueName
     */
    protected function createQueue(Context $context, $queueName)
    {
        $queue = parent::createQueue($context, $queueName);

        $context->subscribe($this->topic, $queue);

        return $queue;
    }

    protected function createTopic(Context $context, $topicName)
    {
        return $this->topic = parent::createTopic($context, $topicName);
    }
}
