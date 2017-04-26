<?php

namespace Enqueue\Null;

use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;

class NullConsumer implements PsrConsumer
{
    /**
     * @var PsrDestination
     */
    private $queue;

    /**
     * @param PsrDestination $queue
     */
    public function __construct(PsrDestination $queue)
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
    public function acknowledge(PsrMessage $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
    }
}
