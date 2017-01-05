<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Consumption\QueueConsumer;

/**
 * @group functional
 */
class QueueConsumerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $queueConsumer = $this->container->get('enqueue.consumption.queue_consumer');

        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);
    }
}
