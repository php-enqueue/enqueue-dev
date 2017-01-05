<?php

namespace Enqueue\Router;

use Enqueue\Psr\Message;

interface RecipientListRouterInterface
{
    /**
     * @param Message $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(Message $message);
}
