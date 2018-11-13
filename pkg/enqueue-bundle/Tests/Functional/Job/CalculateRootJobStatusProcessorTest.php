<?php

namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\JobQueue\CalculateRootJobStatusProcessor;

/**
 * @group functional
 */
class CalculateRootJobStatusProcessorTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $this->markTestSkipped('Configuration for jobs is not yet ready');

        $instance = static::$container->get(CalculateRootJobStatusProcessor::class);

        $this->assertInstanceOf(CalculateRootJobStatusProcessor::class, $instance);
    }
}
