<?php

namespace Enqueue\Client;

use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;

final class PostSend
{
    private $message;

    private $producer;

    private $driver;

    private $transportDestination;

    private $transportMessage;

    public function __construct(
        Message $message,
        ProducerInterface $producer,
        DriverInterface $driver,
        Destination $transportDestination,
        TransportMessage $transportMessage
    ) {
        $this->message = $message;
        $this->producer = $producer;
        $this->driver = $driver;
        $this->transportDestination = $transportDestination;
        $this->transportMessage = $transportMessage;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getProducer(): ProducerInterface
    {
        return $this->producer;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getTransportDestination(): Destination
    {
        return $this->transportDestination;
    }

    public function getTransportMessage(): TransportMessage
    {
        return $this->transportMessage;
    }

    public function isEvent(): bool
    {
        return (bool) $this->message->getProperty(Config::TOPIC);
    }

    public function isCommand(): bool
    {
        return (bool) $this->message->getProperty(Config::COMMAND);
    }

    public function getCommand(): string
    {
        return $this->message->getProperty(Config::COMMAND);
    }

    public function getTopic(): string
    {
        return $this->message->getProperty(Config::TOPIC);
    }
}
