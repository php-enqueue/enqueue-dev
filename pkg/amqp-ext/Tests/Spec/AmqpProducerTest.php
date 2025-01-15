<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\ProducerSpec;

/**
 * @group functional
 */
class AmqpProducerTest extends ProducerSpec
{
    protected function createProducer()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext()->createProducer();
    }
}
