<?php

namespace Enqueue\Mongodb;

use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class MongodbProducer implements PsrProducer
{
    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var int|float|null
     */
    private $deliveryDelay;

    /**
     * @var int|float|null
     */
    private $timeToLive;

    /**
     * @var MongodbContext
     */
    private $context;

    /**
     * @param MongodbContext $context
     */
    public function __construct(MongodbContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param MongodbDestination $destination
     * @param MongodbMessage     $message
     *
     * @throws Exception
     */
    public function send(PsrDestination $destination, PsrMessage $message)
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
        if (is_scalar($body) || null === $body) {
            $body = (string) $body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $publishedAt = null !== $message->getPublishedAt() ?
            $message->getPublishedAt() :
            (int) (microtime(true) * 10000)
        ;

        $mongoMessage = [
            'published_at' => $publishedAt,
            'body' => $body,
            'headers' => $message->getHeaders(),
            'properties' => $message->getProperties(),
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
            throw new Exception('The transport has failed to send the message due to some internal error.', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        $this->timeToLive = $timeToLive;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }
}
