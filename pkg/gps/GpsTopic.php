<?php

namespace Enqueue\Gps;

use Interop\Queue\PsrTopic;

class GpsTopic implements PsrTopic
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
    public function getTopicName()
    {
        return $this->name;
    }
}
