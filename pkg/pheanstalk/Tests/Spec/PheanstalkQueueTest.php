<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Interop\Queue\Spec\PsrQueueSpec;

class PheanstalkQueueTest extends PsrQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new PheanstalkDestination(self::EXPECTED_QUEUE_NAME);
    }
}
