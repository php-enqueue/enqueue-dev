<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;

class DummyAsyncEvent extends Event implements AsyncEvent
{

    public $queueName = 'dummyQueue';

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }
}
