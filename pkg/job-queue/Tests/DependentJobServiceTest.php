<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\JobQueue\DependentJobContext;
use Enqueue\JobQueue\DependentJobService;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\Job;
use PHPUnit\Framework\MockObject\MockObject;

class DependentJobServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DependentJobService($this->createJobStorageMock());
    }

    public function testShouldThrowIfJobIsNotRootJob()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());

        $context = new DependentJobContext($job);

        $service = new DependentJobService($this->createJobStorageMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only root jobs allowed but got child. jobId: "12345"');

        $service->saveDependentJob($context);
    }

    public function testShouldSaveDependentJobs()
    {
        $job = new Job();
        $job->setId(12345);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);

                return true;
            })
        ;

        $context = new DependentJobContext($job);
        $context->addDependentJob('job-topic', 'job-message', 'job-priority');

        $service = new DependentJobService($storage);

        $service->saveDependentJob($context);

        $expectedDependentJobs = [
            'dependentJobs' => [
                [
                    'topic' => 'job-topic',
                    'message' => 'job-message',
                    'priority' => 'job-priority',
                ],
            ],
        ];

        $this->assertEquals($expectedDependentJobs, $job->getData());
    }

    /**
     * @return MockObject|\Enqueue\JobQueue\Doctrine\JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }
}
