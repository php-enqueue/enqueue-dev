<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class DbalDestination implements PsrTopic, PsrQueue
{
    /**
     * @var string
     */
    private $destinationName;

    public function __construct(string $name)
    {
        $this->destinationName = $name;
    }

    public function getQueueName(): string
    {
        return $this->destinationName;
    }

    public function getTopicName(): string
    {
        return $this->destinationName;
    }
}
