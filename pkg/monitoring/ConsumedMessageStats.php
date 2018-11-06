<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class ConsumedMessageStats implements Stats
{
    const STATUS_ACK = 'acknowledged';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REQUEUED = 'requeued';
    const STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    protected $consumerId;

    /**
     * @var int
     */
    protected $timestampMs;

    /**
     * @var int
     */
    protected $receivedAtMs;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var string
     */
    protected $status;

    public function __construct(
        string $consumerId,
        int $timestampMs,
        int $receivedAtMs,
        string $queue,
        array $headers,
        array $properties,
        string $status
    ) {
        $this->consumerId = $consumerId;
        $this->timestampMs = $timestampMs;
        $this->receivedAtMs = $receivedAtMs;
        $this->queue = $queue;
        $this->headers = $headers;
        $this->properties = $properties;
        $this->status = $status;
    }

    public function getConsumerId(): string
    {
        return $this->consumerId;
    }

    public function getTimestampMs(): int
    {
        return $this->timestampMs;
    }

    public function getReceivedAtMs(): int
    {
        return $this->receivedAtMs;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
