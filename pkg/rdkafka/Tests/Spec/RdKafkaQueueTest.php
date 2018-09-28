<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\Spec\QueueSpec;

/**
 * @group rdkafka
 */
class RdKafkaQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new RdKafkaTopic(self::EXPECTED_QUEUE_NAME);
    }
}
