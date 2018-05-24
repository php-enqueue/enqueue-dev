<?php

namespace Enqueue\AsyncEventDispatcher;

interface AsyncEvent
{
    /**
     * @return string
     */
    public function getQueueName();
}
