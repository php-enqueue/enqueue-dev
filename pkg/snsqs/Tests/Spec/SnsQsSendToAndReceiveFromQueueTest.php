<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Interop\Queue\Context;
use Interop\Queue\Spec\SendToAndReceiveFromQueueSpec;

/**
 * @group functional
 *
 * @retry 5
 */
class SnsQsSendToAndReceiveFromQueueTest extends SendToAndReceiveFromQueueSpec
{
    use SnsQsFactoryTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanUpSnsQs();
    }

    protected function createContext()
    {
        return $this->createSnsQsContext();
    }

    protected function createQueue(Context $context, $queueName)
    {
        return $this->createSnsQsQueue($queueName);
    }
}
