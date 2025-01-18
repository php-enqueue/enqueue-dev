<?php

namespace Enqueue\AsyncEventDispatcher;

class AsyncEventDispatcher extends AbstractAsyncEventDispatcher
{
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $this->parentDispatch($event, $eventName);

        return $this->trueEventDispatcher->dispatch($event, $eventName);
    }

    protected function parentDispatch($event, $eventName)
    {
        return parent::dispatch($event, $eventName);
    }
}
