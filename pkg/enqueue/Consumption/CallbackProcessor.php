<?php

namespace Enqueue\Consumption;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class CallbackProcessor implements PsrProcessor
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
    public function process(PsrMessage $message, PsrContext $context)
    {
        return call_user_func($this->callback, $message, $context);
    }
}
