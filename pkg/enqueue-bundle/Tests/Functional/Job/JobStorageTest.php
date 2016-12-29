<?php
namespace Enqueue\Bundle\Tests\Functional\Job;

use Enqueue\JobQueue\JobStorage;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

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
