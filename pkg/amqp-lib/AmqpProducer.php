<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrTopic;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpProducer implements PsrProducer
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
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

        $amqpProperties = $message->getHeaders();

        if ($appProperties = $message->getProperties()) {
            $amqpProperties['application_headers'] = new AMQPTable($appProperties);
        }

        $amqpMessage = new LibAMQPMessage($message->getBody(), $amqpProperties);

        if ($destination instanceof AmqpTopic) {
            $this->channel->basic_publish(
                $amqpMessage,
                $destination->getTopicName(),
                $destination->getRoutingKey(),
                $message->isMandatory(),
                $message->isImmediate(),
                $message->getTicket()
            );
        } else {
            $this->channel->basic_publish(
                $amqpMessage,
                '',
                $destination->getQueueName(),
                $message->isMandatory(),
                $message->isImmediate(),
                $message->getTicket()
            );
        }
    }
}
