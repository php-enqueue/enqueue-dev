<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpDestination as InteropAmqpDestination;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpProducer as InteropAmqpProducer;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Topic;

class AmqpProducer implements InteropAmqpProducer, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var int|null
     */
    private $timeToLive;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var int
     */
    private $deliveryDelay;

    /**
     * @var AmqpContext
     */
    private $context;

    public function __construct(Channel $channel, AmqpContext $context)
    {
        $this->channel = $channel;
        $this->context = $context;
    }

    /**
     * @param InteropAmqpTopic|InteropAmqpQueue $destination
     * @param InteropAmqpMessage                $message
     */
    public function send(Destination $destination, Message $message): void
    {
        $destination instanceof Topic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpQueue::class)
        ;

        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        try {
            $this->doSend($destination, $message);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return self
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (null === $this->delayStrategy) {
            throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
        }

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

    private function doSend(InteropAmqpDestination $destination, InteropAmqpMessage $message): void
    {
        if (null !== $this->priority && null === $message->getPriority()) {
            $message->setPriority($this->priority);
        }

        if (null !== $this->timeToLive && null === $message->getExpiration()) {
            $message->setExpiration($this->timeToLive);
        }

        $amqpProperties = $message->getHeaders();
        $amqpProperties = $this->context->convertHeadersToBunnyNotation($amqpProperties);

        if (array_key_exists('timestamp', $amqpProperties) && null !== $amqpProperties['timestamp']) {
            $amqpProperties['timestamp'] = \DateTime::createFromFormat('U', (string) $amqpProperties['timestamp']);
        }

        if ($appProperties = $message->getProperties()) {
            $amqpProperties['application_headers'] = $appProperties;
        }

        if ($this->deliveryDelay) {
            $this->delayStrategy->delayMessage($this->context, $destination, $message, $this->deliveryDelay);
        } elseif ($destination instanceof InteropAmqpTopic) {
            $this->channel->publish(
                $message->getBody(),
                $amqpProperties,
                $destination->getTopicName(),
                $message->getRoutingKey(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
        } else {
            $this->channel->publish(
                $message->getBody(),
                $amqpProperties,
                '',
                $destination->getQueueName(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
        }
    }
}
