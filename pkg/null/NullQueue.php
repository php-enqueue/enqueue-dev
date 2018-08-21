<?php

namespace Enqueue\Null;

use Interop\Queue\PsrQueue;

class NullQueue implements PsrQueue
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
