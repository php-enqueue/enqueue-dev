<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\Consumer;
use Interop\Queue\Destination;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class NullConsumer implements Consumer
{
    /**
     * @var Destination
     */
    private $queue;

    public function __construct(Destination $queue)
    {
        $this->queue = $queue;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return NullMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        return null;
    }

    /**
     * @return NullMessage
     */
    public function receiveNoWait(): ?Message
    {
        return null;
    }

    public function acknowledge(Message $message): void
    {
    }

    public function reject(Message $message, bool $requeue = false): void
    {
    }
}
