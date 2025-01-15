<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkMessage;
use Interop\Queue\Spec\MessageSpec;

class PheanstalkMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new PheanstalkMessage();
    }
}
