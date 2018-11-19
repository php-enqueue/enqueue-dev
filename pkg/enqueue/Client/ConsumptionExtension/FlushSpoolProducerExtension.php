<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\EndExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;

class FlushSpoolProducerExtension implements PostMessageReceivedExtensionInterface, EndExtensionInterface
{
    /**
     * @var SpoolProducer
     */
    private $producer;

    public function __construct(SpoolProducer $producer)
    {
        $this->producer = $producer;
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $this->producer->flush();
    }

    public function onEnd(End $context): void
    {
        $this->producer->flush();
    }
}
