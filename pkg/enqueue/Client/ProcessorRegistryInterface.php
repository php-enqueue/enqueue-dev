<?php

namespace Enqueue\Client;

use Interop\Queue\PsrProcessor;

interface ProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return PsrProcessor
     */
    public function get($processorName);
}
