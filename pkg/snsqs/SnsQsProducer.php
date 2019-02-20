<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsProducer;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsProducer;
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
    private $snsContext;

    /**
     * @var SnsProducer
     */
    private $snsProducer;

    /**
     * @var SqsContext
     */
    private $sqsContext;

    /**
     * @var SqsProducer
     */
    private $sqsProducer;

    public function __construct(SnsContext $snsContext, SqsContext $sqsContext)
    {
        $this->snsContext = $snsContext;
        $this->sqsContext = $sqsContext;
    }

    /**
     * @param SnsQsTopic   $destination
     * @param SnsQsMessage $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, SnsQsMessage::class);

        if (false == $destination instanceof SnsQsTopic && false == $destination instanceof SnsQsQueue) {
            throw new InvalidDestinationException(sprintf(
                'The destination must be an instance of [%s|%s] but got %s.',
                SnsQsTopic::class, SnsQsQueue::class,
                is_object($destination) ? get_class($destination) : gettype($destination)
            ));
        }

        if ($destination instanceof SnsQsTopic) {
            $snsMessage = $this->snsContext->createMessage(
                $message->getBody(),
                $message->getProperties(),
                $message->getHeaders()
            );

            $this->getSnsProducer()->send($destination, $snsMessage);
        } else {
            $sqsMessage = $this->sqsContext->createMessage(
                $message->getBody(),
                $message->getProperties(),
                $message->getHeaders()
            );

            $this->getSqsProducer()->send($destination, $sqsMessage);
        }
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->getSnsProducer()->setDeliveryDelay($deliveryDelay);
        $this->getSqsProducer()->setDeliveryDelay($deliveryDelay);

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->getSnsProducer()->getDeliveryDelay();
    }

    public function setPriority(int $priority = null): Producer
    {
        $this->getSnsProducer()->setPriority($priority);
        $this->getSqsProducer()->setPriority($priority);

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->getSnsProducer()->getPriority();
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->getSnsProducer()->setTimeToLive($timeToLive);
        $this->getSqsProducer()->setTimeToLive($timeToLive);

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->getSnsProducer()->getTimeToLive();
    }

    private function getSnsProducer(): SnsProducer
    {
        if (null === $this->snsProducer) {
            $this->snsProducer = $this->snsContext->createProducer();
        }

        return $this->snsProducer;
    }

    private function getSqsProducer(): SqsProducer
    {
        if (null === $this->sqsProducer) {
            $this->sqsProducer = $this->sqsContext->createProducer();
        }

        return $this->sqsProducer;
    }
}
