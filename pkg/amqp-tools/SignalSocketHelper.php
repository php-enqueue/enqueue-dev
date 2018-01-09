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
        if ($this->handlers) {
            throw new \LogicException('The handlers property should be empty but it is not. The afterSocket method might not have been called.');
        }
        if (null !== $this->wasThereSignal) {
            throw new \LogicException('The wasSignal property should be null but it is not. The afterSocket method might not have been called.');
        }

        $this->wasThereSignal = false;

        foreach ([SIGTERM, SIGQUIT, SIGINT] as $signal) {
            /** @var callable $handler */
            if ($handler = pcntl_signal_get_handler(SIGTERM)) {
                pcntl_signal($signal, function ($signal) use ($handler) {
                    $this->wasThereSignal = true;

                    $handler($signal);
                });

                $this->handlers[$signal] = $handler;
            }
        }
    }

    public function afterSocket()
    {
        $this->wasThereSignal = null;

        foreach ($this->handlers as $signal => $handler) {
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
