<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Test\RetryTrait;
use Enqueue\Test\SqsExtension;
use Interop\Queue\Context;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 * @retry 5
 */
class SqsSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    use RetryTrait;
    use SqsExtension;
    use CreateSqsQueueTrait;

    /**
     * @var SqsContext
     */
    private $context;

    protected function tearDown()
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

    protected function createQueue(Context $context, $queueName): SqsDestination
    {
        return $this->createSqsQueue($context, $queueName);
    }
}
