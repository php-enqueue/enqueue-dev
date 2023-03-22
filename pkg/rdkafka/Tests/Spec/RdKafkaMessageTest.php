<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaMessage;
use Interop\Queue\Spec\MessageSpec;

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
