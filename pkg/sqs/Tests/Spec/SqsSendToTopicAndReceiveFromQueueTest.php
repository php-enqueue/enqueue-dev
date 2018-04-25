<?php

namespace Enqueue\Sqs\Tests\Spec;

use Interop\Queue\Spec\SendToTopicAndReceiveFromQueueSpec;

/**
 * @group functional
 */
class SqsSendToTopicAndReceiveFromQueueTest extends SendToTopicAndReceiveFromQueueSpec
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
