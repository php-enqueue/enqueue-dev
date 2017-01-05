<?php

namespace Enqueue\Client;

use Enqueue\Psr\Processor;

interface ProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return Processor
     */
    public function get($processorName);
}
