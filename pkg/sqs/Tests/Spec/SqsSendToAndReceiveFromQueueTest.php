<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class SqsSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    /**
     * @var SqsContext
     */
    private $context;

    /**
     * @var SqsDestination
     */
    private $queue;

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

        return $this->context = $factory->createContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsContext $context
     */
    protected function createQueue(PsrContext $context, $queueName)
    {
        $queueName = $queueName.time();

        $this->queue = $context->createQueue($queueName);
        $context->declareQueue($this->queue);

        return $this->queue;
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->context && $this->queue) {
            $this->context->deleteQueue($this->queue);
        }
    }
}
