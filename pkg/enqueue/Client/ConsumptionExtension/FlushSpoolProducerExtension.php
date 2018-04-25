<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class FlushSpoolProducerExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var SpoolProducer
     */
    private $producer;

    /**
     * @param SpoolProducer $producer
     */
    public function __construct(SpoolProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->producer->flush();
    }

    public function onInterrupted(Context $context)
    {
        $this->producer->flush();
    }
}
