<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Test\RetryTrait;
use Enqueue\Test\SqsExtension;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 * @retry 5
 */
class SqsSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    use SqsExtension;
    use RetryTrait;
    use CreateSqsQueueTrait;

    /**
     * @var SqsContext
     */
    private $context;

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->context && $this->queue) {
            $this->context->deleteQueue($this->queue);
        }
    }

    protected function createContext(): SqsContext
    {
        return $this->context = $this->buildSqsContext();
    }

    protected function createTopic(Context $context, $queueName): SqsDestination
    {
        return $this->createSqsQueue($context, $queueName);
    }
}
