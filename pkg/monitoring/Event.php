<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class Event
{
    /**
     * @var string
     */
    protected $consumerId;

    /**
     * @var int
     */
    protected $timestampMs;

    public function __construct(string $consumerId, int $timestampMs)
    {
        $this->consumerId = $consumerId;
        $this->timestampMs = $timestampMs;
    }

    public function getConsumerId(): string
    {
        return $this->consumerId;
    }

    public function getTimestampMs(): int
    {
        return $this->timestampMs;
    }
}
