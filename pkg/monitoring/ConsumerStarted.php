<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class ConsumerStarted extends Event
{
    /**
     * @var string[]
     */
    protected $queues;

    public function __construct(
        string $consumerId,
        int $timestampMs,
        array $queues
    ) {
        parent::__construct($consumerId, $timestampMs);

        $this->queues = $queues;
    }
}
