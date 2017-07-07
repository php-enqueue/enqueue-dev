<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class PheanstalkMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new PheanstalkMessage();
    }
}
