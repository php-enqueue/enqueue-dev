<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class ConsumedMessageStats implements Stats
{
    public const STATUS_ACK = 'acknowledged';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REQUEUED = 'requeued';
    public const STATUS_FAILED = 'failed';

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
     * @var string
     */
    protected $messageId;

    /**
     * @var string
     */
    protected $correlationId;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var bool;
     */
    protected $redelivered;

    /**
     * @var string
     */
    protected $status;

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
        int $receivedAtMs,
        string $queue,
        ?string $messageId,
        ?string $correlationId,
        array $headers,
        array $properties,
        bool $redelivered,
        string $status,
        ?string $errorClass = null,
        ?string $errorMessage = null,
        ?int $errorCode = null,
        ?string $errorFile = null,
        ?int $errorLine = null,
        ?string $trace = null,
    ) {
        $this->consumerId = $consumerId;
        $this->timestampMs = $timestampMs;
        $this->receivedAtMs = $receivedAtMs;
        $this->queue = $queue;
        $this->messageId = $messageId;
        $this->correlationId = $correlationId;
        $this->headers = $headers;
        $this->properties = $properties;
        $this->redelivered = $redelivered;
        $this->status = $status;

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

    public function getReceivedAtMs(): int
    {
        return $this->receivedAtMs;
    }

    public function getQueue(): string
    {
        return $this->queue;
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

    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    public function getStatus(): string
    {
        return $this->status;
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
