<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Message;
use Stomp\Transport\Frame;

class StompMessage implements Message
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
     * @var Frame
     */
    private $frame;

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
        if (null === $value) {
            unset($this->properties[$name]);
        } else {
            $this->properties[$name] = $value;
        }
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
        if (null === $value) {
            unset($this->headers[$name]);
        } else {
            $this->headers[$name] = $value;
        }
    }

    public function getHeader(string $name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    public function isPersistent(): bool
    {
        return $this->getHeader('persistent', false);
    }

    public function setPersistent(bool $persistent): void
    {
        $this->setHeader('persistent', $persistent);
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

    public function getFrame(): ?Frame
    {
        return $this->frame;
    }

    public function setFrame(Frame $frame = null): void
    {
        $this->frame = $frame;
    }

    public function setReplyTo(string $replyTo = null): void
    {
        $this->setHeader('reply-to', $replyTo);
    }

    public function getReplyTo(): ?string
    {
        return $this->getHeader('reply-to');
    }
}
