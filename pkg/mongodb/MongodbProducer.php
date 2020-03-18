<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\Destination;
use Interop\Queue\Exception\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class MongodbProducer implements Producer
{
    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var int|null
     */
    private $deliveryDelay;

    /**
     * @var int|null
     */
    private $timeToLive;

    /**
     * @var MongodbContext
     */
    private $context;

    public function __construct(MongodbContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param MongodbDestination $destination
     * @param MongodbMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, MongodbDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, MongodbMessage::class);

        if (null !== $this->priority && null === $message->getPriority()) {
            $message->setPriority($this->priority);
        }
        if (null !== $this->deliveryDelay && null === $message->getDeliveryDelay()) {
            $message->setDeliveryDelay($this->deliveryDelay);
        }
        if (null !== $this->timeToLive && null === $message->getTimeToLive()) {
            $message->setTimeToLive($this->timeToLive);
        }

        $body = $message->getBody();

        $publishedAt = null !== $message->getPublishedAt() ?
            $message->getPublishedAt() :
            (int) (microtime(true) * 10000)
        ;

        $mongoMessage = [
            'published_at' => $publishedAt,
            'body' => $body,
            'headers' => JSON::encode($message->getHeaders()),
            'properties' => JSON::encode($message->getProperties()),
            'priority' => $message->getPriority(),
            'queue' => $destination->getName(),
            'redelivered' => $message->isRedelivered(),
        ];

        $delay = $message->getDeliveryDelay();
        if ($delay) {
            if (!is_int($delay)) {
                throw new \LogicException(sprintf(
                    'Delay must be integer but got: "%s"',
                    is_object($delay) ? get_class($delay) : gettype($delay)
                ));
            }

            if ($delay <= 0) {
                throw new \LogicException(sprintf('Delay must be positive integer but got: "%s"', $delay));
            }

            $mongoMessage['delayed_until'] = time() + (int) $delay / 1000;
        }

        $timeToLive = $message->getTimeToLive();
        if ($timeToLive) {
            if (!is_int($timeToLive)) {
                throw new \LogicException(sprintf(
                    'TimeToLive must be integer but got: "%s"',
                    is_object($timeToLive) ? get_class($timeToLive) : gettype($timeToLive)
                ));
            }

            if ($timeToLive <= 0) {
                throw new \LogicException(sprintf('TimeToLive must be positive integer but got: "%s"', $timeToLive));
            }

            $mongoMessage['time_to_live'] = time() + (int) $timeToLive / 1000;
        }

        try {
            $collection = $this->context->getCollection();
            $collection->insertOne($mongoMessage);
        } catch (\Exception $e) {
            throw new Exception('The transport has failed to send the message due to some internal error.', $e->getCode(), $e);
        }
    }

    /**
     * @return self
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
     * @return self
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
     * @return self
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
