<?php
namespace Enqueue\Psr;

interface MessageListener
{
    /**
     * @param Message $message
     */
    public function onMessage(Message $message);
}
