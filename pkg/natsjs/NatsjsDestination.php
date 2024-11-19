<?php

/*
    Copyright (c) 2024 g41797
    SPDX-License-Identifier: MIT
*/

namespace Enqueue\Natsjs;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class NatsjsDestination implements Queue, Topic
{
    private string $destinationName;

    public function __construct(string $destinationName)
    {
        $this->destinationName = $destinationName;
    }

    public function getQueueName(): string
    {
        return $this->getName();
    }

    public function getTopicName(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->destinationName;
    }
}
