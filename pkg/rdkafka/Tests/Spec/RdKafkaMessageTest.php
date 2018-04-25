<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaMessage;
use Interop\Queue\Spec\PsrMessageSpec;

/**
 * @group rdkafka
 */
class RdKafkaMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new RdKafkaMessage();
    }
}
