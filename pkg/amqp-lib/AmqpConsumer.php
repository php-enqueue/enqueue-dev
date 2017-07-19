<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\Exception;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
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
     * @var Buffer
     */
    private $buffer;

    /**
     * @var bool
     */
    private $isInit;

    /**
     * @var string
     */
    private $receiveMethod;

    /**
     * @var AmqpMessage
     */
    private $receivedMessage;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @param AMQPChannel $channel
     * @param AmqpQueue   $queue
     * @param Buffer      $buffer
     * @param string      $receiveMethod
     */
    public function __construct(AMQPChannel $channel, AmqpQueue $queue, Buffer $buffer, $receiveMethod)
    {
        $this->channel = $channel;
        $this->queue = $queue;
        $this->buffer = $buffer;
        $this->receiveMethod = $receiveMethod;

        $this->isInit = false;
    }

    /**
     * @return AmqpQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receive($timeout = 0)
    {
        if ('basic_get' == $this->receiveMethod) {
            return $this->receiveBasicGet($timeout);
        }

        if ('basic_consume' == $this->receiveMethod) {
            return $this->receiveBasicConsume($timeout);
        }

        throw new \LogicException('The "receiveMethod" is not supported');
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

    /**
     * @param int $timeout
     *
     * @return AmqpMessage|null
     */
    private function receiveBasicGet($timeout)
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
     * @param int $timeout
     *
     * @return AmqpMessage|null
     */
    private function receiveBasicConsume($timeout)
    {
        if (false === $this->isInit) {
            $callback = function (LibAMQPMessage $message) {
                $receivedMessage = $this->convertMessage($message);
                $consumerTag = $message->delivery_info['consumer_tag'];

                if ($this->consumerTag === $consumerTag) {
                    $this->receivedMessage = $receivedMessage;
                } else {
                    // not our message, put it to buffer and continue.
                    $this->buffer->push($consumerTag, $receivedMessage);
                }
            };

            $this->channel->basic_qos(0, 1, false);

            $consumerTag = $this->channel->basic_consume(
                $this->queue->getQueueName(),
                $this->queue->getConsumerTag(),
                $this->queue->isNoLocal(),
                $this->queue->isNoAck(),
                $this->queue->isExclusive(),
                $this->queue->isNoWait(),
                $callback
            );

            $this->consumerTag = $consumerTag ?: $this->queue->getConsumerTag();

            if (empty($this->consumerTag)) {
                throw new Exception('Got empty consumer tag');
            }

            $this->isInit = true;
        }

        if ($message = $this->buffer->pop($this->consumerTag)) {
            return $message;
        }

        $this->receivedMessage = null;

        try {
            $this->channel->wait(null, false, $timeout);
        } catch (AMQPTimeoutException $e) {
        }

        return $this->receivedMessage;
    }
}
