<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;

class NullSubscriptionConsumer implements SubscriptionConsumer
{
    public function consume(int $timeout = 0): void
    {
    }

    public function subscribe(Consumer $consumer, callable $callback): void
    {
    }

    public function unsubscribe(Consumer $consumer): void
    {
    }

    public function unsubscribeAll(): void
    {
    }
}
