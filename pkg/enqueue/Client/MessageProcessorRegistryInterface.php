<?php
namespace Enqueue\Client;

use Enqueue\Consumption\MessageProcessorInterface;

interface MessageProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return MessageProcessorInterface
     */
    public function get($processorName);
}
