<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\Spec\PsrQueueSpec;

/**
 * @group rdkafka
 */
class RdKafkaQueueTest extends PsrQueueSpec
{
    protected function createQueue()
    {
        return new RdKafkaTopic(self::EXPECTED_QUEUE_NAME);
    }
}
