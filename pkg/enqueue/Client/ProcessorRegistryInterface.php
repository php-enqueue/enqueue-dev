<?php

namespace Enqueue\Client;

use Interop\Queue\PsrProcessor;

interface ProcessorRegistryInterface
{
    public function get(string $processorName): PsrProcessor;
}
