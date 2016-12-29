<?php
namespace Enqueue\AmqpExt;

use Enqueue\Psr\Destination;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\Message;
use Enqueue\Psr\Producer;
use Enqueue\Psr\Topic;

class AmqpProducer implements Producer
{
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
    public function send(Destination $destination, Message $message)
    {
        $destination instanceof Topic
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
}
