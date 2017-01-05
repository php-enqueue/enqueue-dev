<?php

namespace Enqueue\Transport\Null;

use Enqueue\Psr\Destination;
use Enqueue\Psr\Message;
use Enqueue\Psr\Producer;

class NullProducer implements Producer
{
    /**
     * {@inheritdoc}
     */
    public function send(Destination $destination, Message $message)
    {
    }
}
