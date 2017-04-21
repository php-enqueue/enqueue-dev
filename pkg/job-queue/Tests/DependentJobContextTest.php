<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\JobQueue\DependentJobContext;
use Enqueue\JobQueue\Job;

class DependentJobContextTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DependentJobContext(new Job());
    }

    public function testShouldReturnJob()
    {
        $job = new Job();

        $context = new DependentJobContext($job);

        $this->assertSame($job, $context->getJob());
    }

    public function testCouldAddAndGetDependentJobs()
    {
        $context = new DependentJobContext(new Job());

        $context->addDependentJob('topic1', 'message1');
        $context->addDependentJob('topic2', 'message2', 12345);

        $expectedDependentJobs = [
            [
                'topic' => 'topic1',
                'message' => 'message1',
                'priority' => null,
            ],
            [
                'topic' => 'topic2',
                'message' => 'message2',
                'priority' => 12345,
            ],
        ];

        $this->assertEquals($expectedDependentJobs, $context->getDependentJobs());
    }
}
