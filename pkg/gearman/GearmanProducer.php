<?php

declare(strict_types=1);

namespace Enqueue\Gearman;

use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class GearmanProducer implements Producer
{
    /**
     * @var \GearmanClient
     */
    private $client;

    public function __construct(\GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param GearmanDestination $destination
     * @param GearmanMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GearmanDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, GearmanMessage::class);

        $this->client->doBackground($destination->getName(), json_encode($message));

        $code = $this->client->returnCode();
        if (\GEARMAN_SUCCESS !== $code) {
            throw new \GearmanException(sprintf('The return code is not %s (GEARMAN_SUCCESS) but %s', \GEARMAN_SUCCESS, $code));
        }
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getPriority(): ?int
    {
        return null;
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        if (null === $timeToLive) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
