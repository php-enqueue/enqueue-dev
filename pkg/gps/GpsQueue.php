<?php

namespace Enqueue\Gps;

use Interop\Queue\PsrQueue;

class GpsQueue implements PsrQueue
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getQueueName(): string
    {
        return $this->name;
    }
}
