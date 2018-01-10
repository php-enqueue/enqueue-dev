<?php

namespace Enqueue\AmqpTools;

class SignalSocketHelper
{
    /**
     * @var callable[]
     */
    private $handlers;

    /**
     * @var bool
     */
    private $wasThereSignal;

    public function __construct()
    {
        $this->handlers = [];
    }

    public function beforeSocket()
    {
        // PHP 7.1 and higher
        if (false == function_exists('pcntl_signal_get_handler')) {
            return;
        }

        $signals = [SIGTERM, SIGQUIT, SIGINT];

        if ($this->handlers) {
            throw new \LogicException('The handlers property should be empty but it is not. The afterSocket method might not have been called.');
        }
        if (null !== $this->wasThereSignal) {
            throw new \LogicException('The wasThereSignal property should be null but it is not. The afterSocket method might not have been called.');
        }

        $this->wasThereSignal = false;

        foreach ($signals as $signal) {
            /** @var callable $handler */
            $handler = pcntl_signal_get_handler($signal);

            pcntl_signal($signal, function ($signal) use ($handler) {
                $this->wasThereSignal = true;

                $handler && $handler($signal);
            });

            $handler && $this->handlers[$signal] = $handler;
        }
    }

    public function afterSocket()
    {
        // PHP 7.1 and higher
        if (false == function_exists('pcntl_signal_get_handler')) {
            return;
        }

        $signals = [SIGTERM, SIGQUIT, SIGINT];

        $this->wasThereSignal = null;

        foreach ($signals as $signal) {
            $handler = isset($this->handlers[$signal]) ? $this->handlers[$signal] : SIG_DFL;

            pcntl_signal($signal, $handler);
        }

        $this->handlers = [];
    }

    /**
     * @return bool
     */
    public function wasThereSignal()
    {
        return (bool) $this->wasThereSignal;
    }
}
