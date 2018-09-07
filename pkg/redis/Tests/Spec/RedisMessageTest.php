<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisMessage;
use Interop\Queue\Spec\PsrMessageSpec;

/**
 * @group Redis
 */
class RedisMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new RedisMessage();
    }
}
