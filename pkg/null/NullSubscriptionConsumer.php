<?php

namespace Enqueue\Null;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

class NullSubscriptionConsumer implements PsrSubscriptionConsumer
{
    public function consume(int $timeout = 0): void
    {
    }

    public function subscribe(PsrConsumer $consumer, callable $callback): void
    {
    }

    public function unsubscribe(PsrConsumer $consumer): void
    {
    }

    public function unsubscribeAll(): void
    {
    }
}
