<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
