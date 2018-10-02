<?php

declare(strict_types=1);

namespace Enqueue\Consumption;

interface QueueConsumerRegistryInterface
{
    public function get(string $name): QueueConsumerInterface;
}
