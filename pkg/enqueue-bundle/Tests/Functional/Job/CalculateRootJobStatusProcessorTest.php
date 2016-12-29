<?php
namespace Enqueue\EnqueueBundle\Tests\Functional\Job;

use Enqueue\JobQueue\CalculateRootJobStatusProcessor;
use Enqueue\EnqueueBundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class CalculateRootJobStatusProcessorTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->container->get('enqueue.job.calculate_root_job_status_processor');

        $this->assertInstanceOf(CalculateRootJobStatusProcessor::class, $instance);
    }
}
