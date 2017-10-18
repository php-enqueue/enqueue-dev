<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

/**
 * @group rdkafka
 */
class RdKafkaConnectionFactoryTest extends PsrConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new RdKafkaConnectionFactory();
    }
}
