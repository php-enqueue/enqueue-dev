<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Interop\Queue\Spec\QueueSpec;

class PheanstalkQueueTest extends QueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new PheanstalkDestination(self::EXPECTED_QUEUE_NAME);
    }
}
