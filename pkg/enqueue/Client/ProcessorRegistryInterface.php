<?php

namespace Enqueue\Client;

use Enqueue\Psr\PsrProcessor;

interface ProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return PsrProcessor
     */
    public function get($processorName);
}
