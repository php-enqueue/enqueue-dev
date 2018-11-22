<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class WampMessage implements Message
{
    use MessageTrait;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }
}
