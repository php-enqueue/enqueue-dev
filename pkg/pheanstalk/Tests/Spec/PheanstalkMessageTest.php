<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkMessage;
use Enqueue\Psr\Spec\PsrMessageSpec;

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
