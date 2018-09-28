<?php

namespace Enqueue\Client;

use Interop\Queue\Processor;

interface ProcessorRegistryInterface
{
    public function get(string $processorName): Processor;
}
