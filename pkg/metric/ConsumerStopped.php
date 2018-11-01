<?php

declare(strict_types=1);

namespace Enqueue\Metric;

class ConsumerStopped extends Event
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
        array $queues,
        int $startedAtMs,
        int $received,
        int $acknowledged,
        int $rejected,
        int $requeued,
        string $errorClass = null,
        string $errorMessage = null,
        int $errorCode = null,
        string $errorFile = null,
        int $errorLine = null,
        string $trace = null
    ) {
        parent::__construct($consumerId, $timestampMs);

        $this->queues = $queues;
        $this->startedAtMs = $startedAtMs;
        $this->received = $received;
        $this->acknowledged = $acknowledged;
        $this->rejected = $rejected;
        $this->requeued = $requeued;

        $this->errorClass = $errorClass;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->errorFile = $errorFile;
        $this->errorLine = $errorLine;
        $this->trance = $trace;
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
