<?php

namespace Enqueue\Client;

final class DriverPreSend
{
    private $message;

    private $producer;

    private $driver;

    public function __construct(Message $message, ProducerInterface $producer, DriverInterface $driver)
    {
        $this->message = $message;
        $this->producer = $producer;
        $this->driver = $driver;
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
