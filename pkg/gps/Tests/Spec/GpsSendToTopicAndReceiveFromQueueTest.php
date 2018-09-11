<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsContext;
use Enqueue\Test\GpsExtension;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class GpsSendToTopicAndReceiveFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
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
