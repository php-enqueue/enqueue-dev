<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkMessage;
use Enqueue\Test\ClassExtensionTrait;
use Pheanstalk\Job;
use PHPUnit\Framework\TestCase;

class PheanstalkMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldAllowGetJobPreviouslySet()
    {
        $job = new Job('anId', 'aData');

        $message = new PheanstalkMessage();
        $message->setJob($job);

        $this->assertSame($job, $message->getJob());
    }
}
