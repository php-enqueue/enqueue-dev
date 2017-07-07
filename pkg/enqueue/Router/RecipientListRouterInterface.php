<?php

namespace Enqueue\Router;

use Interop\Queue\PsrMessage;

interface RecipientListRouterInterface
{
    /**
     * @param PsrMessage $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(PsrMessage $message);
}
