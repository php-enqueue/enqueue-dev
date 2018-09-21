<?php

namespace Enqueue\Client\Driver;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Interop\Queue\PsrTopic;

/**
 * @method RedisContext getContext
 * @method RedisDestination createQueue(string $name)
 */
class RedisDriver extends GenericDriver
{
    public function __construct(RedisContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    /**
     * @return RedisDestination
     */
    protected function createRouterTopic(): PsrTopic
    {
        return $this->createQueue($this->getConfig()->getRouterQueueName());
    }
}
