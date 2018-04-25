<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 */
class AmqpProducerTest extends PsrProducerSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        return $factory->createContext()->createProducer();
    }
}
