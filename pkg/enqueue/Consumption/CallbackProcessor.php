<?php

namespace Enqueue\Consumption;

use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

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
    public function process(InteropMessage $message, Context $context)
    {
        return call_user_func($this->callback, $message, $context);
    }
}
