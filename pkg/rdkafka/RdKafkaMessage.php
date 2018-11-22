<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\Message;
use RdKafka\Message as VendorMessage;

class RdKafkaMessage implements Message
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var bool
     */
    private $redelivered;

    /**
     * @var int
     */
    private $partition;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var Message
     */
    private $kafkaMessage;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }

    public function getProperty(string $name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getHeader(string $name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = $redelivered;
    }

    public function setCorrelationId(string $correlationId = null): void
    {
        $this->setHeader('correlation_id', (string) $correlationId);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getHeader('correlation_id');
    }

    public function setMessageId(string $messageId = null): void
    {
        $this->setHeader('message_id', (string) $messageId);
    }

    public function getMessageId(): ?string
    {
        return $this->getHeader('message_id');
    }

    public function getTimestamp(): ?int
    {
        $value = $this->getHeader('timestamp');

        return null === $value ? null : (int) $value;
    }

    public function setTimestamp(int $timestamp = null): void
    {
        $this->setHeader('timestamp', $timestamp);
    }

    public function setReplyTo(string $replyTo = null): void
    {
        $this->setHeader('reply_to', $replyTo);
    }

    public function getReplyTo(): ?string
    {
        return $this->getHeader('reply_to');
    }

    public function getPartition(): ?int
    {
        return $this->partition;
    }

    public function setPartition(int $partition = null): void
    {
        $this->partition = $partition;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key = null): void
    {
        $this->key = $key;
    }

    public function getKafkaMessage(): ?VendorMessage
    {
        return $this->kafkaMessage;
    }

    public function setKafkaMessage(VendorMessage $message = null): void
    {
        $this->kafkaMessage = $message;
    }
}
