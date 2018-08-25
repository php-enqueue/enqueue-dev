<?php

namespace Enqueue\Null;

use Interop\Queue\PsrTopic;

class NullTopic implements PsrTopic
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
