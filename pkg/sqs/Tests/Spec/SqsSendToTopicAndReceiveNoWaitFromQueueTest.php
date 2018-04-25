<?php

namespace Enqueue\Sqs\Tests\Spec;

use Interop\Queue\Spec\SendToTopicAndReceiveNoWaitFromQueueSpec;

/**
 * @group functional
 */
class SqsSendToTopicAndReceiveNoWaitFromQueueTest extends SendToTopicAndReceiveNoWaitFromQueueSpec
{
    public function test()
    {
        $this->markTestSkipped('The SQS does not support it');
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        throw new \LogicException('Should not be ever called');
    }
}
