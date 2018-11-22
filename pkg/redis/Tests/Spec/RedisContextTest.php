<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisContextTest extends ContextSpec
{
    use RedisExtension;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildPhpRedisContext();
    }
}
