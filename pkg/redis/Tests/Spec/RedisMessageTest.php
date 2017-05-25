<?php
namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Psr\Spec\PsrMessageSpec;
use Enqueue\Redis\RedisMessage;

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