<?php

declare(strict_types=1);

namespace Enqueue;

use Interop\Queue\Processor;

interface ProcessorRegistryInterface
{
    public function get(string $processorName): Processor;
}
