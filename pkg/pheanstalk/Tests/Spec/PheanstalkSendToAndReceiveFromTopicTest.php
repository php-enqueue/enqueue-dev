<?php

namespace Enqueue\Pheanstalk;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrTopic;
use Enqueue\Psr\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class PheanstalkSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
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
