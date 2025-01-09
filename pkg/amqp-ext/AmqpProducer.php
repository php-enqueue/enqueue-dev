<?php

namespace Enqueue\AmqpExt;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer as InteropAmqpProducer;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
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
     * @var int|float|null
     */
    private $timeToLive;

    /**
     * @var \AMQPChannel
     */
    private $amqpChannel;

    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var int
     */
    private $deliveryDelay;

    public function __construct(\AMQPChannel $ampqChannel, AmqpContext $context)
    {
        $this->amqpChannel = $ampqChannel;
        $this->context = $context;
    }

    /**
     * @param AmqpTopic|AmqpQueue $destination
     * @param AmqpMessage         $message
     */
    public function send(Destination $destination, Message $message): void
    {
        $destination instanceof Topic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        try {
            $this->doSend($destination, $message);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function setDeliveryDelay(?int $deliveryDelay = null): Producer
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

    public function setPriority(?int $priority = null): Producer
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setTimeToLive(?int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }

    private function doSend(AmqpDestination $destination, AmqpMessage $message): void
    {
        if (null !== $this->priority && null === $message->getPriority()) {
            $message->setPriority($this->priority);
        }

        if (null !== $this->timeToLive && null === $message->getExpiration()) {
            $message->setExpiration($this->timeToLive);
        }

        $amqpAttributes = $message->getHeaders();

        if ($message->getProperties()) {
            $amqpAttributes['headers'] = $message->getProperties();
        }

        if ($this->deliveryDelay) {
            $this->delayStrategy->delayMessage($this->context, $destination, $message, $this->deliveryDelay);
        } elseif ($destination instanceof AmqpTopic) {
            $amqpExchange = new \AMQPExchange($this->amqpChannel);
            $amqpExchange->setType($destination->getType());
            $amqpExchange->setName($destination->getTopicName());
            $amqpExchange->setFlags(Flags::convertTopicFlags($destination->getFlags()));
            $amqpExchange->setArguments($destination->getArguments());

            $amqpExchange->publish(
                $message->getBody(),
                $message->getRoutingKey(),
                Flags::convertMessageFlags($message->getFlags()),
                $amqpAttributes
            );
        } else {
            /** @var AmqpQueue $destination */
            $amqpExchange = new \AMQPExchange($this->amqpChannel);
            $amqpExchange->setType(AMQP_EX_TYPE_DIRECT);
            $amqpExchange->setName('');

            $amqpExchange->publish(
                $message->getBody(),
                $destination->getQueueName(),
                Flags::convertMessageFlags($message->getFlags()),
                $amqpAttributes
            );
        }
    }
}
