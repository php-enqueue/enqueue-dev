<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 */
class SqsSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $factory = new SqsConnectionFactory([
            'key' => getenv('AWS__SQS__KEY'),
            'secret' => getenv('AWS__SQS__SECRET'),
            'region' => getenv('AWS__SQS__REGION'),
        ]);

        return $factory->createContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsContext $context
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        $topicName = $topicName.time();

        $topic = $context->createTopic($topicName);
        $context->declareQueue($topic);

        return $topic;
    }
}
