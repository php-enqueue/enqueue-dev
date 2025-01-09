<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class ConsumerStats implements Stats
{
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
    protected $startedAtMs;

    /**
     * @var int
     */
    protected $finishedAtMs;

    /**
     * @var bool
     */
    protected $started;

    /**
     * @var bool
     */
    protected $finished;

    /**
     * @var bool
     */
    protected $failed;

    /**
     * @var string[]
     */
    protected $queues;

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

    /**
     * @var string
     */
    protected $errorClass;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var int
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorFile;

    /**
     * @var int
     */
    protected $errorLine;

    /**
     * @var string
     */
    protected $trance;

    public function __construct(
        string $consumerId,
        int $timestampMs,
        int $startedAtMs,
        ?int $finishedAtMs,
        bool $started,
        bool $finished,
        bool $failed,
        array $queues,
        int $received,
        int $acknowledged,
        int $rejected,
        int $requeued,
        int $memoryUsage,
        float $systemLoad,
        ?string $errorClass = null,
        ?string $errorMessage = null,
        ?int $errorCode = null,
        ?string $errorFile = null,
        ?int $errorLine = null,
        ?string $trace = null
    ) {
        $this->consumerId = $consumerId;
        $this->timestampMs = $timestampMs;
        $this->startedAtMs = $startedAtMs;
        $this->finishedAtMs = $finishedAtMs;

        $this->started = $started;
        $this->finished = $finished;
        $this->failed = $failed;

        $this->queues = $queues;
        $this->startedAtMs = $startedAtMs;
        $this->received = $received;
        $this->acknowledged = $acknowledged;
        $this->rejected = $rejected;
        $this->requeued = $requeued;

        $this->memoryUsage = $memoryUsage;
        $this->systemLoad = $systemLoad;

        $this->errorClass = $errorClass;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->errorFile = $errorFile;
        $this->errorLine = $errorLine;
        $this->trance = $trace;
    }

    public function getConsumerId(): string
    {
        return $this->consumerId;
    }

    public function getTimestampMs(): int
    {
        return $this->timestampMs;
    }

    public function getStartedAtMs(): int
    {
        return $this->startedAtMs;
    }

    public function getFinishedAtMs(): ?int
    {
        return $this->finishedAtMs;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function getQueues(): array
    {
        return $this->queues;
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

    public function getErrorClass(): ?string
    {
        return $this->errorClass;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getErrorFile(): ?string
    {
        return $this->errorFile;
    }

    public function getErrorLine(): ?int
    {
        return $this->errorLine;
    }

    public function getTrance(): ?string
    {
        return $this->trance;
    }
}
