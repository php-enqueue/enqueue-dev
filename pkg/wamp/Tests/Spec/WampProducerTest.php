<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Test\WampExtension;
use Interop\Queue\Spec\ProducerSpec;

/**
 * @group functional
 * @group Wamp
 */
class WampProducerTest extends ProducerSpec
{
    use WampExtension;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->buildWampContext()->createProducer();
    }
}
