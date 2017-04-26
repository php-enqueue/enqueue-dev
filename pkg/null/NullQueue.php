<?php

namespace Enqueue\Null;

use Enqueue\Psr\PsrQueue;

class NullQueue implements PsrQueue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->name;
    }
}
