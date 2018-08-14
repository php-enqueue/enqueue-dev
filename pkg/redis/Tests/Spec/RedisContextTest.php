<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Test\RedisExtension;
use Interop\Queue\Spec\PsrContextSpec;

/**
 * @group functional
 * @group Redis
 */
class RedisContextTest extends PsrContextSpec
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
