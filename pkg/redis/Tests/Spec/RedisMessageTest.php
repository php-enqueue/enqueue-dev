<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisMessage;
use Interop\Queue\Spec\PsrMessageSpec;

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
