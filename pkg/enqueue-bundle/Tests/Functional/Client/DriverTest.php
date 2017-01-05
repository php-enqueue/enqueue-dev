<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\DriverInterface;

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
