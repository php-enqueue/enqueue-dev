<?php

namespace Enqueue\Gps;

use Interop\Queue\PsrQueue;

class GpsQueue implements PsrQueue
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
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->name;
    }
}
