<?php

namespace Enqueue\AmqpExt;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

class AmqpSubscriptionConsumer implements PsrSubscriptionConsumer
{
    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(PsrConsumer $consumer, callable $callback)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe(PsrConsumer $consumer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeAll()
    {
    }
}
