<?php

namespace Enqueue\Consumption;

use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Message;
use Enqueue\Psr\Processor;

class CallbackProcessor implements Processor
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, PsrContext $context)
    {
        return call_user_func($this->callback, $message, $context);
    }
}
