<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\GpsContext;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

class GpsSendToTopicAndReceiveNoWaitFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
{
    private $topic;

    protected function createContext()
    {
        return (new GpsConnectionFactory())->createContext();
    }

    /**
     * @param GpsContext $context
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queue = parent::createQueue($context, $queueName);

        $context->subscribe($this->topic, $queue);

        return $queue;
    }

    protected function createTopic(PsrContext $context, $topicName)
    {
        return $this->topic = parent::createTopic($context, $topicName);
    }
}
