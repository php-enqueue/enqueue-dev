<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

/**
 * @group rdkafka
 */
class RdKafkaConnectionFactoryTest extends ConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new RdKafkaConnectionFactory();
    }
}
