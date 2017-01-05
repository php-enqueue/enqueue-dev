<?php

namespace Enqueue\Transport\Null;

use Enqueue\Psr\Consumer;
use Enqueue\Psr\Destination;
use Enqueue\Psr\Message;

class NullConsumer implements Consumer
{
    /**
     * @var Destination
     */
    private $queue;

    /**
     * @param Destination $queue
     */
    public function __construct(Destination $queue)
    {
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function receive($timeout = 0)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(Message $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Message $message, $requeue = false)
    {
    }
}
