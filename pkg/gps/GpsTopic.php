<?php

namespace Enqueue\Gps;

use Interop\Queue\PsrTopic;

class GpsTopic implements PsrTopic
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getTopicName(): string
    {
        return $this->name;
    }
}
