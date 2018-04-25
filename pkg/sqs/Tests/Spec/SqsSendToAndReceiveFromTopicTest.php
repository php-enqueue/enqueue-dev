<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Test\RetryTrait;
use Enqueue\Test\SqsExtension;
use Interop\Queue\PsrContext;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 * @retry 5
 */
class SqsSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    use SqsExtension;
    use RetryTrait;

    /**
     * @var SqsContext
     */
    private $context;

    /**
     * @var SqsDestination
     */
    private $queue;

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->context && $this->queue) {
            $this->context->deleteQueue($this->queue);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->context = $this->buildSqsContext();
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsContext $context
     */
    protected function createTopic(PsrContext $context, $topicName)
    {
        $topicName = $topicName.time();

        $this->queue = $context->createTopic($topicName);
        $context->declareQueue($this->queue);

        return $this->queue;
    }
}
