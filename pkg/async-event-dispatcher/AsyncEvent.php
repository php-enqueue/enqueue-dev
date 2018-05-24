<?php

namespace Enqueue\AsyncEventDispatcher;

/**
 * Interface AsyncEvent
 * @package Enqueue\AsyncEventDispatcher
 */
interface AsyncEvent
{
    /**
     * @return string
     */
    public function getQueueName();
}
