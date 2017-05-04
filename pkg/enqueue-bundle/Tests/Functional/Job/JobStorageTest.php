<?php

namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\JobQueue\Doctrine\JobStorage;

/**
 * @group functional
 */
class JobStorageTest extends WebTestCase
{
    public function testCouldGetJobStorageAsServiceFromContainer()
    {
        $instance = $this->container->get('enqueue.job.storage');

        $this->assertInstanceOf(JobStorage::class, $instance);
    }
}
