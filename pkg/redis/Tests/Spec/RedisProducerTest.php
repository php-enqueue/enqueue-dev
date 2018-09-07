<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\PsrProducerSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisProducerTest extends PsrProducerSpec
{
    use RedisExtension;

    /**
     * {@inheritdoc}
     */
    protected function createProducer()
    {
        return $this->buildPhpRedisContext()->createProducer();
    }
}
