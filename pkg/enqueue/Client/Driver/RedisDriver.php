<?php

declare(strict_types=1);

namespace Enqueue\Client\Driver;

use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;

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
}
