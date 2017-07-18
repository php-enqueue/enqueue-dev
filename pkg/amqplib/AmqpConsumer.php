<?php

namespace Enqueue\Amqplib;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpConsumer implements PsrConsumer
{
    private $channel;
    private $queue;

    public function __construct(AMQPChannel $channel, AmqpQueue $queue)
    {
        $this->channel = $channel;
        $this->queue = $queue;
    }

    public function getQueue()
    {
        return $this->queue;
    }

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

    public function receiveNoWait()
    {
        if ($message = $this->channel->basic_get($this->queue->getQueueName())) {
            return $this->convertMessage($message);
        }
    }

    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_ack($message->getDeliveryTag());
    }

    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_reject($message->getDeliveryTag(), $requeue);
    }

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
