<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpConnectionFactory;
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
