<?php
namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\JobQueue\JobRunner;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class JobRunnerTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->container->get('enqueue.job.runner');

        $this->assertInstanceOf(JobRunner::class, $instance);
    }
}
