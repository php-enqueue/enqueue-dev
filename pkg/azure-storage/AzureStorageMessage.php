<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class AzureStorageMessage implements Message
{
    use MessageTrait;
    
    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;

        $this->redelivered = false;
        $this->visibilityTimeout = 0;
    }

    public function getVisibilityTimeout()
    {
        return $this->visibilityTimeout;
    }

    public function setVisibilityTimeout($visibilityTimeout)
    {
        $this->visibilityTimeout = $visibilityTimeout;
        return $this;
    }
}