<?php

namespace Enqueue\Router;

use Interop\Queue\Message as InteropMessage;

interface RecipientListRouterInterface
{
    /**
     * @return \Traversable|Recipient[]
     */
    public function route(InteropMessage $message);
}
