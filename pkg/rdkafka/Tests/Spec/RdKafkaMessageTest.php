<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaMessage;
use Interop\Queue\Spec\MessageSpec;

/**
 * @group rdkafka
 */
class RdKafkaMessageTest extends MessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new RdKafkaMessage();
    }
}
