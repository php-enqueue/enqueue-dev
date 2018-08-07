<?php

namespace Enqueue\AmqpTools;

use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

/**
 * @deprecated this is BC layer, will be removed in 0.9
 */
final class SubscriptionConsumer implements PsrSubscriptionConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    public function __construct(AmqpContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
        $this->context->consume($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(PsrConsumer $consumer, callable $callback)
    {
        $this->context->subscribe($consumer, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe(PsrConsumer $consumer)
    {
        $this->context->unsubscribe($consumer);
    }

    /**
     * TODO.
     *
     * {@inheritdoc}
     */
    public function unsubscribeAll()
    {
        throw new \LogicException('Not implemented');
    }
}
