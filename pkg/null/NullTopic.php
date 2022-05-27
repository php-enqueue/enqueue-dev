<?php

namespace Enqueue\NoEffect;

use Interop\Queue\Topic;

class NullTopic implements Topic
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
