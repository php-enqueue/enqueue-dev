<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpConsumer implements PsrConsumer
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @param AMQPChannel $channel
     * @param AmqpQueue   $queue
     */
    public function __construct(AMQPChannel $channel, AmqpQueue $queue)
    {
        $this->channel = $channel;
        $this->queue = $queue;
    }

    /**
     * @return AmqpQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param int $timeout
     *
     * @return AmqpMessage|null
     */
    public function receive($timeout = 0)
    {
        $end = microtime(true) + ($timeout / 1000);

        while (0 === $timeout || microtime(true) < $end) {
            if ($message = $this->receiveNoWait()) {
                return $message;
            }

            usleep(100000); //100ms
        }
    }

    /**
     * @return AmqpMessage|null
     */
    public function receiveNoWait()
    {
        if ($message = $this->channel->basic_get($this->queue->getQueueName())) {
            return $this->convertMessage($message);
        }
    }

    /**
     * @param AmqpMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_ack($message->getDeliveryTag());
    }

    /**
     * @param AmqpMessage $message
     * @param bool        $requeue
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_reject($message->getDeliveryTag(), $requeue);
    }

    /**
     * @param LibAMQPMessage $amqpMessage
     *
     * @return AmqpMessage
     */
    private function convertMessage(LibAMQPMessage $amqpMessage)
    {
        $headers = new AMQPTable($amqpMessage->get_properties());
        $headers = $headers->getNativeData();

        $properties = [];
        if (isset($headers['application_headers'])) {
            $properties = $headers['application_headers'];
        }
        unset($headers['application_headers']);

        $message = new AmqpMessage($amqpMessage->getBody(), $properties, $headers);
        $message->setDeliveryTag($amqpMessage->delivery_info['delivery_tag']);
        $message->setRedelivered($amqpMessage->delivery_info['redelivered']);

        return $message;
    }
}
