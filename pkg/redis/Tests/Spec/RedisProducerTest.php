<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\ProducerSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisProducerTest extends ProducerSpec
{
    use RedisExtension;

    protected function createProducer()
    {
        return $this->buildPhpRedisContext()->createProducer();
    }
}
