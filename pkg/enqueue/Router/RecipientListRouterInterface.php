<?php

namespace Enqueue\Router;

use Interop\Queue\Message as InteropMessage;

interface RecipientListRouterInterface
{
    /**
     * @param InteropMessage $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(InteropMessage $message);
}
