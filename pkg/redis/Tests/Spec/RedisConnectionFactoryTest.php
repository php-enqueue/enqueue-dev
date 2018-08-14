<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

/**
 * @group Redis
 */
class RedisConnectionFactoryTest extends PsrConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new RedisConnectionFactory();
    }
}
