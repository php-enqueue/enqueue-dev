<?php

namespace Enqueue\AsyncEventDispatcher;

interface Registry
{
    /**
     * @param string $eventName
     *
     * @return string
     */
    public function getTransformerNameForEvent($eventName);

    /**
     * @param string $name
     *
     * @return EventTransformer
     */
    public function getTransformer($name);
}
