<?php
namespace Enqueue\EnqueueBundle\Tests\Functional\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\EnqueueBundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class DriverTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $driver = $this->container->get('enqueue.client.driver');

        $this->assertInstanceOf(DriverInterface::class, $driver);
    }
}
