<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class ConsumerStats extends Event
{
    /**
     * @var string[]
     */
    protected $queues;

    /**
     * @var int
     */
    protected $startedAtMs;

    /**
     * @var int
     */
    protected $received;

    /**
     * @var int
     */
    protected $acknowledged;

    /**
     * @var int
     */
    protected $rejected;

    /**
     * @var int
     */
    protected $requeued;

    /**
     * @var int
     */
    protected $memoryUsage;

    /**
     * @var float
     */
    protected $systemLoad;

    public function __construct(
        string $consumerId,
        int $timestampMs,
        array $queues,
        int $startedAtMs,
        int $received,
        int $acknowledged,
        int $rejected,
        int $requeued,
        int $memoryUsage,
        float $systemLoad
    ) {
        parent::__construct($consumerId, $timestampMs);

        $this->queues = $queues;
        $this->startedAtMs = $startedAtMs;
        $this->received = $received;
        $this->acknowledged = $acknowledged;
        $this->rejected = $rejected;
        $this->requeued = $requeued;

        $this->memoryUsage = $memoryUsage;
        $this->systemLoad = $systemLoad;
    }

    public function getQueues(): array
    {
        return $this->queues;
    }

    public function getStartedAtMs(): int
    {
        return $this->startedAtMs;
    }

    public function getReceived(): int
    {
        return $this->received;
    }

    public function getAcknowledged(): int
    {
        return $this->acknowledged;
    }

    public function getRejected(): int
    {
        return $this->rejected;
    }

    public function getRequeued(): int
    {
        return $this->requeued;
    }

    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    public function getSystemLoad(): float
    {
        return $this->systemLoad;
    }
}
