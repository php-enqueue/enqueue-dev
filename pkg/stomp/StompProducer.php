<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Stomp\Client;
use Stomp\Transport\Message as StompLibMessage;

class StompProducer implements PsrProducer
{
    /**
     * @var Client
     */
    private $stomp;

    /**
     * @param Client $stomp
     */
    public function __construct(Client $stomp)
    {
        $this->stomp = $stomp;
    }

    /**
     * @param StompDestination $destination
     * @param StompMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $headers = array_merge($message->getHeaders(), $destination->getHeaders());
        $headers = StompHeadersEncoder::encode($headers, $message->getProperties());

        $stompMessage = new StompLibMessage($message->getBody(), $headers);

        $this->stomp->send($destination->getQueueName(), $stompMessage);
    }

    public function setDeliveryDelay(int $deliveryDelay = null): PsrProducer
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

    public function setPriority(int $priority = null): PsrProducer
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

    public function setTimeToLive(int $timeToLive = null): PsrProducer
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
