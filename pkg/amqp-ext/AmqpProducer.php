<?php

namespace Enqueue\AmqpExt;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;

class AmqpProducer implements PsrProducer
{
    /**
     * @var float
     */
    private $deliveryDelay = PsrMessage::DEFAULT_DELIVERY_DELAY;

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

        $amqpAttributes = $message->getHeaders();

        if ($message->getProperties()) {
            $amqpAttributes['headers'] = $message->getProperties();
        }

        if ($destination instanceof AmqpTopic) {
            $amqpExchange = new \AMQPExchange($this->amqpChannel);
            $amqpExchange->setType($destination->getType());
            $amqpExchange->setName($destination->getTopicName());
            $amqpExchange->setFlags($destination->getFlags());
            $amqpExchange->setArguments($destination->getArguments());

            $amqpExchange->publish(
                $message->getBody(),
                $destination->getRoutingKey(),
                $message->getFlags(),
                $amqpAttributes
            );
        } else {
            $amqpExchange = new \AMQPExchange($this->amqpChannel);
            $amqpExchange->setType(AMQP_EX_TYPE_DIRECT);
            $amqpExchange->setName('');

            $amqpExchange->publish(
                $message->getBody(),
                $destination->getQueueName(),
                $message->getFlags(),
                $amqpAttributes
            );
        }
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
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;
    }
}
