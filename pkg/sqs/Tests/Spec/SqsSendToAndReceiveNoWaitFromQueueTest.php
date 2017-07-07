<?php

namespace Enqueue\Sqs\Tests\Spec;

use Interop\Queue\Spec\SendToAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class SqsSendToAndReceiveNoWaitFromQueueTest extends SendToAndReceiveNoWaitFromQueueSpec
{
    public function test()
    {
        $this->markTestSkipped('The test is fragile. This is how SQS.');
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        throw new \LogicException('Should not be ever called');
    }
}
