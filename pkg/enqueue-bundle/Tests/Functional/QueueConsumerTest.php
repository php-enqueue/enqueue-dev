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
        $queueConsumer = static::$container->get(QueueConsumer::class);

        $this->assertInstanceOf(QueueConsumer::class, $queueConsumer);
    }
}
