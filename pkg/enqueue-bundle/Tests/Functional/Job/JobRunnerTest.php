<?php

namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\JobQueue\JobRunner;

/**
 * @group functional
 */
class JobRunnerTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $this->markTestSkipped('Configuration for jobs is not yet ready');

        $instance = static::$container->get(JobRunner::class);

        $this->assertInstanceOf(JobRunner::class, $instance);
    }
}
