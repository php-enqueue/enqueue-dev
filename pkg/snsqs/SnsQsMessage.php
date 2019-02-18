<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class SnsQsMessage implements Message
{
    use MessageTrait;

    /**
     * @var SqsMessage
     */
    private $sqsMessage;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }

    public function setSqsMessage(SqsMessage $message): void
    {
        $this->sqsMessage = $message;
    }

    public function getSqsMessage(): SqsMessage
    {
        return $this->sqsMessage;
    }
}
