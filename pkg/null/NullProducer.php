<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class NullProducer implements PsrProducer
{
    private $priority;

    private $timeToLive;

    private $deliveryDelay;

    public function send(PsrDestination $destination, PsrMessage $message): void
    {
    }

    /**
     * @return NullProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): PsrProducer
    {
        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    /**
     * @return NullProducer
     */
    public function setPriority(int $priority = null): PsrProducer
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @return NullProducer
     */
    public function setTimeToLive(int $timeToLive = null): PsrProducer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}
