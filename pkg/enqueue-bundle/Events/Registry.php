<?php

namespace Enqueue\Bundle\Events;

interface Registry
{
    /**
     * @param string $eventName
     *
     * @return EventTransformer
     */
    public function getTransformer($eventName);
}
