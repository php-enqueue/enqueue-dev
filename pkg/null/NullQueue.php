<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\Queue;

class NullQueue implements Queue
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
