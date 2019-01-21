<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Types\Type;
use Interop\Queue\Destination;
use Interop\Queue\Exception\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Ramsey\Uuid\Uuid;

class DbalProducer implements Producer
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
     * @var DbalContext
     */
    private $context;

    /**
     * @param DbalContext $context
     */
    public function __construct(DbalContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param DbalDestination $destination
     * @param DbalMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

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

        $dbalMessage = [
            'id' => Uuid::uuid4(),
            'published_at' => $publishedAt,
            'body' => $body,
            'headers' => JSON::encode($message->getHeaders()),
            'properties' => JSON::encode($message->getProperties()),
            'priority' => -1 * $message->getPriority(),
            'queue' => $destination->getQueueName(),
            'redelivered' => false,
            'delivery_id' => null,
            'redeliver_after' => null,
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

            $dbalMessage['delayed_until'] = time() + (int) $delay / 1000;
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

            $dbalMessage['time_to_live'] = time() + (int) $timeToLive / 1000;
        }

        try {
            $rowsAffected = $this->context->getDbalConnection()->insert($this->context->getTableName(), $dbalMessage, [
                'id' => Type::GUID,
                'published_at' => Type::INTEGER,
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'time_to_live' => Type::INTEGER,
                'delayed_until' => Type::INTEGER,
                'redelivered' => Type::SMALLINT,
                'delivery_id' => Type::STRING,
                'redeliver_after' => Type::BIGINT,
            ]);

            if (1 !== $rowsAffected) {
                throw new Exception('The message was not enqueued. Dbal did not confirm that the record is inserted.');
            }
        } catch (\Exception $e) {
            throw new Exception('The transport fails to send the message due to some internal error.', 0, $e);
        }
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    public function setPriority(int $priority = null): Producer
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

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
