<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Test\RetryTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendAndReceiveDelayedMessageFromQueueSpec;

/**
 * @group functional
 * @retry 5
 */
class SqsSendAndReceiveDelayedMessageFromQueueTest extends SendAndReceiveDelayedMessageFromQueueSpec
{
    use RetryTrait;

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
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queueName = $queueName.time();

        $queue = $context->createQueue($queueName);
        $context->declareQueue($queue);

        return $queue;
    }
}
