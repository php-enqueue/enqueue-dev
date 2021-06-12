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

    /**
     * @var array|null
     */
    private $messageAttributes;

    /**
     * See AWS documentation for message attribute structure.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#shape-messageattributevalue
     */
    public function __construct(
        string $body = '',
        array $properties = [],
        array $headers = [],
        array $messageAttributes = null
    ) {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
        $this->messageAttributes = $messageAttributes;
    }

    public function setSqsMessage(SqsMessage $message): void
    {
        $this->sqsMessage = $message;
    }

    public function getSqsMessage(): SqsMessage
    {
        return $this->sqsMessage;
    }

    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    public function setMessageAttributes(?array $messageAttributes): void
    {
        $this->messageAttributes = $messageAttributes;
    }
}
