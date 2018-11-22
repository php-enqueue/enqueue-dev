<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\Message;

class RedisMessage implements Message
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
     * @var string
     */
    private $reservedKey;

    /**
     * @var string
     */
    private $key;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;

        $this->redelivered = false;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
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

    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = (bool) $redelivered;
    }

    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    public function setCorrelationId(string $correlationId = null): void
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getHeader('correlation_id');
    }

    public function setMessageId(string $messageId = null): void
    {
        $this->setHeader('message_id', $messageId);
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

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return (int) $this->getHeader('attempts', 0);
    }

    /**
     * @return int
     */
    public function getTimeToLive(): ?int
    {
        return $this->getHeader('time_to_live');
    }

    /**
     * Set time to live in milliseconds.
     */
    public function setTimeToLive(int $timeToLive = null): void
    {
        $this->setHeader('time_to_live', $timeToLive);
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->getHeader('delivery_delay');
    }

    /**
     * Set delay in milliseconds.
     */
    public function setDeliveryDelay(int $deliveryDelay = null): void
    {
        $this->setHeader('delivery_delay', $deliveryDelay);
    }

    /**
     * @return string
     */
    public function getReservedKey(): ?string
    {
        return $this->reservedKey;
    }

    /**
     * @param string $reservedKey
     */
    public function setReservedKey(string $reservedKey)
    {
        $this->reservedKey = $reservedKey;
    }

    /**
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }
}
