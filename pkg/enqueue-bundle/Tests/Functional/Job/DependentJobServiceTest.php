<?php

namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\JobQueue\DependentJobService;

/**
 * @group functional
 */
class DependentJobServiceTest extends WebTestCase
{
    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->container->get(DependentJobService::class);

        $this->assertInstanceOf(DependentJobService::class, $instance);
    }
}
