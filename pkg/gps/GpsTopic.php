<?php

declare(strict_types=1);

namespace Enqueue\Gps;

use Interop\Queue\Topic;

class GpsTopic implements Topic
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
