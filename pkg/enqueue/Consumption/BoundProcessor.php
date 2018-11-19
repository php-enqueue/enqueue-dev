<?php

namespace Enqueue\Consumption;

use Interop\Queue\Processor;
use Interop\Queue\Queue;

final class BoundProcessor
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var Processor
     */
    private $processor;

    public function __construct(Queue $queue, Processor $processor)
    {
        $this->queue = $queue;
        $this->processor = $processor;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }
}
