<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class SnsMessage implements Message
{
    use MessageTrait;

    private $snsMessageId;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }

    public function getSnsMessageId(): ?string
    {
        return $this->snsMessageId;
    }

    public function setSnsMessageId(?string $snsMessageId): void
    {
        $this->snsMessageId = $snsMessageId;
    }
}
