<?php

declare(strict_types=1);

namespace Enqueue\Gearman;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class GearmanDestination implements PsrQueue, PsrTopic
{
    /**
     * @var string
     */
    private $destinationName;

    public function __construct(string $destinationName)
    {
        $this->destinationName = $destinationName;
    }

    public function getName(): string
    {
        return $this->destinationName;
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
