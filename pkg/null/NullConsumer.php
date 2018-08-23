<?php

namespace Enqueue\Null;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;

class NullConsumer implements PsrConsumer
{
    /**
     * @var PsrDestination
     */
    private $queue;

    public function __construct(PsrDestination $queue)
    {
        $this->queue = $queue;
    }

    public function getQueue(): PsrQueue
    {
        return $this->queue;
    }

    /**
     * @return NullMessage
     */
    public function receive(int $timeout = 0): ?PsrMessage
    {
        return null;
    }

    /**
     * @return NullMessage
     */
    public function receiveNoWait(): ?PsrMessage
    {
        return null;
    }

    public function acknowledge(PsrMessage $message): void
    {
    }

    public function reject(PsrMessage $message, bool $requeue = false): void
    {
    }
}
