<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class SentMessageStats implements Stats
{
    /**
     * @var int
     */
    protected $timestampMs;

    /**
     * @var string
     */
    protected $messageId;

    /**
     * @var string
     */
    protected $correlationId;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var bool
     */
    protected $isTopic;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $properties;

    public function __construct(
        int $timestampMs,
        string $destination,
        bool $isTopic,
        ?string $messageId,
        ?string $correlationId,
        array $headers,
        array $properties
    ) {
        $this->timestampMs = $timestampMs;
        $this->destination = $destination;
        $this->isTopic = $isTopic;
        $this->messageId = $messageId;
        $this->correlationId = $correlationId;
        $this->headers = $headers;
        $this->properties = $properties;
    }

    public function getTimestampMs(): int
    {
        return $this->timestampMs;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function isTopic(): bool
    {
        return $this->isTopic;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
