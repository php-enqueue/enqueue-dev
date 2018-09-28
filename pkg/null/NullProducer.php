<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\Destination;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class NullProducer implements Producer
{
    private $priority;

    private $timeToLive;

    private $deliveryDelay;

    public function send(Destination $destination, Message $message): void
    {
    }

    /**
     * @return NullProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
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
    public function setPriority(int $priority = null): Producer
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
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}
