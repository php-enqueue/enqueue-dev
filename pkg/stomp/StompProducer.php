<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Stomp\Client;
use Stomp\Transport\Message as StompLibMessage;

class StompProducer implements Producer
{
    /**
     * @var Client
     */
    private $stomp;

    public function __construct(Client $stomp)
    {
        $this->stomp = $stomp;
    }

    /**
     * @param StompDestination $destination
     * @param StompMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $headers = array_merge($message->getHeaders(), $destination->getHeaders());
        $headers = StompHeadersEncoder::encode($headers, $message->getProperties());

        $stompMessage = new StompLibMessage($message->getBody(), $headers);

        $this->stomp->send($destination->getQueueName(), $stompMessage);
    }

    /**
     * @return $this|Producer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (empty($deliveryDelay)) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    /**
     * @throws PriorityNotSupportedException
     *
     * @return $this|Producer
     */
    public function setPriority(int $priority = null): Producer
    {
        if (empty($priority)) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    /**
     * @return $this|Producer
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        if (empty($timeToLive)) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
