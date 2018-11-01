<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class MessageStats extends Event
{
    const STATUS_ACK = 'acknowledged';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REQUEUED = 'requeued';
    const STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    private $queue;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var string
     */
    private $status;

    /**
     * @var int
     */
    private $receivedAtMs;

    public function __construct(
        string $consumerId,
        int $timestampMs,
        int $receivedAtMs,
        string $queue,
        array $headers,
        array $properties,
        string $status
    ) {
        parent::__construct($consumerId, $timestampMs);

        $this->queue = $queue;
        $this->receivedAtMs = $receivedAtMs;
        $this->headers = $headers;
        $this->properties = $properties;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getReceivedAtMs(): int
    {
        return $this->receivedAtMs;
    }
}
