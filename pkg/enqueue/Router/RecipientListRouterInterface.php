<?php

namespace Enqueue\Router;

use Enqueue\Psr\PsrMessage;

interface RecipientListRouterInterface
{
    /**
     * @param PsrMessage $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(PsrMessage $message);
}
