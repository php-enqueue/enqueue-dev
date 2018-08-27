<?php

namespace Enqueue\Client;

class PreDriverSend
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
        return Config::COMMAND_TOPIC !== $this->message->getProperty(Config::PARAMETER_TOPIC_NAME);
    }

    public function getCommand(): string
    {
        return $this->message->getProperty(Config::PARAMETER_COMMAND_NAME);
    }

    public function getTopic(): string
    {
        return $this->message->getProperty(Config::PARAMETER_TOPIC_NAME);
    }
}
