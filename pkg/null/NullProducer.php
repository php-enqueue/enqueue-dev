<?php

namespace Enqueue\Null;

use Interop\Queue\CompletionListener;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class NullProducer implements PsrProducer
{
    /**
     * @var CompletionListener
     */
    private $completionListener;

    /**
     * @var float
     */
    private $deliveryDelay = PsrMessage::DEFAULT_DELIVERY_DELAY;

    /**
     * {@inheritdoc}
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setCompletionListener(CompletionListener $listener = null)
    {
        $this->completionListener = $listener;
    }

    /**
     * @return CompletionListener|null
     */
    public function getCompletionListener()
    {
        return $this->completionListener;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;
    }
}
