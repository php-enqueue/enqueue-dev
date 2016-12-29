<?php
namespace Enqueue\Consumption;

use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Message;

class CallbackMessageProcessor implements MessageProcessorInterface
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
