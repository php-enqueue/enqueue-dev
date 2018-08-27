<?php

namespace Enqueue\Client;

class PreSend
{
    private $message;

    private $originalMessage;

    private $commandOrTopic;

    private $producer;

    private $driver;

    public function __construct(
        string $commandOrTopic,
        Message $message,
        ProducerInterface $producer,
        DriverInterface $driver
    ) {
        $this->message = $message;
        $this->commandOrTopic = $commandOrTopic;
        $this->producer = $producer;
        $this->driver = $driver;

        $this->originalMessage = clone $message;
    }

    public function getCommandOrTopic(): string
    {
        return $this->commandOrTopic;
    }

    public function changeCommandOrTopic(string $commandOrTopic): void
    {
        $this->commandOrTopic = $commandOrTopic;
    }

    public function changeBody($body, string $contentType = null): void
    {
        $this->message->setBody($body);

        if (null !== $contentType) {
            $this->message->setContentType($contentType);
        }
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getOriginalMessage(): Message
    {
        return $this->originalMessage;
    }

    public function getProducer(): ProducerInterface
    {
        return $this->producer;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }
}
