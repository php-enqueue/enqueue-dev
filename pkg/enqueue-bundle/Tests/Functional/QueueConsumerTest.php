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
        $queueConsumer = static::$container->get('test_enqueue.client.default.queue_consumer');
        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);

        $queueConsumer = static::$container->get('test_enqueue.transport.default.queue_consumer');
        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);
    }
}
