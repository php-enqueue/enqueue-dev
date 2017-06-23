<?php

namespace Enqueue\Pheanstalk;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrTopic;
use Enqueue\Psr\Spec\SendToAndReceiveNoWaitFromTopicSpec;

/**
 * @group functional
 */
class PheanstalkSendToAndReceiveNoWaitFromTopicTest extends SendToAndReceiveNoWaitFromTopicSpec
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
     * @param string     $topicName
     *
     * @return PsrTopic
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        return $context->createTopic($topicName.time());
    }
}
