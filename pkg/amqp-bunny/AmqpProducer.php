<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Bunny\ChannelModeEnum;
use Bunny\Client;
use Bunny\Protocol\MethodBasicAckFrame;
use Bunny\Protocol\MethodBasicNackFrame;
use Bunny\Protocol\MethodFrame;
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

    /**
     * @var bool
     */
    private $confirmModeEnabled = false;

    /**
     * @var float|null
     */
    private $timeout;

    /**
     * @var bool
     */
    private $ackCallbackAdded = false;

    /**
     * @var int|null
     */
    private $pendingDeliveryTag;

    public function __construct(Channel $channel, AmqpContext $context)
    {
        $this->channel = $channel;
        $this->context = $context;
    }

    public function enableConfirmMode(float $timeout)
    {
        $this->confirmModeEnabled = true;
        $this->timeout = $timeout;
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
            $this->doSendWithPossibleConfirm($destination, $message);
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

    private function doSendWithPossibleConfirm(InteropAmqpDestination $destination, InteropAmqpMessage $message): void
    {
        if ($this->confirmModeEnabled) {
            $this->ensureConfirmMode();
        }

        $result = $this->doSend($destination, $message);

        if ($this->confirmModeEnabled) {
            $this->waitForDelivery($result);
        }
    }

    private function doSend(InteropAmqpDestination $destination, InteropAmqpMessage $message)
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

            return null;
        } elseif ($destination instanceof InteropAmqpTopic) {
            return $this->channel->publish(
                $message->getBody(),
                $amqpProperties,
                $destination->getTopicName(),
                $message->getRoutingKey(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
        }

        return $this->channel->publish(
                $message->getBody(),
                $amqpProperties,
                '',
                $destination->getQueueName(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
    }

    private function ensureConfirmMode()
    {
        if (ChannelModeEnum::CONFIRM !== $this->channel->getMode()) {
            $this->channel->confirmSelect();
        }

        if (!$this->ackCallbackAdded) {
            $this->channel->addAckListener(function (MethodFrame $frame) {
                $this->handleMethodFrame($frame);
            });
            $this->ackCallbackAdded = true;
        }
    }

    private function waitForDelivery(?int $deliveryTag)
    {
        if (null === $deliveryTag) {
            return;
        }

        $this->pendingDeliveryTag = $deliveryTag;
        $this->channel->getClient()->run($this->timeout);
        if (null !== $this->pendingDeliveryTag) {
            throw new Exception(sprintf('No ACK got in %ss for sent message with confirm mode', $this->timeout));
        }
    }

    private function handleMethodFrame(MethodFrame $frame)
    {
        if (!$frame instanceof MethodBasicAckFrame && !$frame instanceof MethodBasicNackFrame) {
            throw new Exception(sprintf('Unexpected frame received: %s', get_class($frame)));
        }

        if ($this->pendingDeliveryTag !== $frame->deliveryTag) {
            // probably different listener will handle this frame
            return;
        }

        if ($frame instanceof MethodBasicNackFrame) {
            throw new Exception('NACK was got for sent message');
        }

        $this->pendingDeliveryTag = null;

        /** @var Client $client */
        $client = $this->channel->getClient();
        $client->stop();
    }
}
