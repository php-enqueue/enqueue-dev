<?php
namespace Enqueue\EnqueueBundle\Tests\Functional\Job;

use Enqueue\JobQueue\DependentJobService;
use Enqueue\EnqueueBundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class DependentJobServiceTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->container->get('enqueue.job.dependent_job_service');

        $this->assertInstanceOf(DependentJobService::class, $instance);
    }
}
