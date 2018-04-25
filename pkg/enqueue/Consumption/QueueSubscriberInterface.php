<?php

namespace Enqueue\Consumption;

interface QueueSubscriberInterface
{
    /**
     * The result must contain a set of queue names a you expect them to see in the broker
     * or the name you use to get the queue object from the context.
     *
     * @return string[]
     */
    public static function getSubscribedQueues();
}
