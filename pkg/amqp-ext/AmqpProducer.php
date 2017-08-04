<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer as InteropAmqpProducer;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Queue\DeliveryDelayNotSupportedException;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrTopic;

class AmqpProducer implements InteropAmqpProducer
{
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
     * @param \AMQPChannel $ampqChannel
     */
    public function __construct(\AMQPChannel $ampqChannel)
    {
        $this->amqpChannel = $ampqChannel;
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpTopic|AmqpQueue $destination
     * @param AmqpMessage         $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        $destination instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class)
        ;

        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

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

        if ($destination instanceof AmqpTopic) {
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

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return null;
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

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }
}
