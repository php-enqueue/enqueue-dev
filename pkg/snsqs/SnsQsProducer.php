<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsProducer;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class SnsQsProducer implements Producer
{
    /**
     * @var SnsContext
     */
    private $context;

    /**
     * @var SnsProducer
     */
    private $producer;

    public function __construct(SnsContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param SnsQsTopic $destination
     * @param SnsQsMessage $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SnsQsTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, SnsQsMessage::class);

        $snsMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());

        $this->getProducer()->send($destination, $snsMessage);
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->getProducer()->setDeliveryDelay($deliveryDelay);

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->getProducer()->getDeliveryDelay();
    }

    public function setPriority(int $priority = null): Producer
    {
        $this->getProducer()->setPriority($priority);

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->getProducer()->getPriority();
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->getProducer()->setTimeToLive($timeToLive);

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->getProducer()->getTimeToLive();
    }

    private function getProducer(): SnsProducer
    {
        if (null === $this->producer) {
            $this->producer = $this->context->createProducer();
        }

        return $this->producer;
    }
}
