<?php

namespace Enqueue\AmqpLib;

use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class AmqpConsumer implements InteropAmqpConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var InteropAmqpQueue
     */
    private $queue;

    /**
     * @var Buffer
     */
    private $buffer;

    /**
     * @var string
     */
    private $receiveMethod;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @param AmqpContext      $context
     * @param InteropAmqpQueue $queue
     * @param Buffer           $buffer
     * @param string           $receiveMethod
     */
    public function __construct(AmqpContext $context, InteropAmqpQueue $queue, Buffer $buffer, $receiveMethod)
    {
        $this->context = $context;
        $this->channel = $context->getLibChannel();
        $this->queue = $queue;
        $this->buffer = $buffer;
        $this->receiveMethod = $receiveMethod;
        $this->flags = self::FLAG_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * {@inheritdoc}
     */
    public function clearFlags()
    {
        $this->flags = self::FLAG_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function addFlag($flag)
    {
        $this->flags |= $flag;
    }

    /**
     * {@inheritdoc}
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * {@inheritdoc}
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return InteropAmqpMessage|null
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
     * @return InteropAmqpMessage|null
     */
    public function receiveNoWait()
    {
        if ($message = $this->channel->basic_get($this->queue->getQueueName(), (bool) ($this->getFlags() & InteropAmqpConsumer::FLAG_NOACK))) {
            return $this->context->convertMessage($message);
        }
    }

    /**
     * @param InteropAmqpMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->channel->basic_ack($message->getDeliveryTag());
    }

    /**
     * @param InteropAmqpMessage $message
     * @param bool               $requeue
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        $this->channel->basic_reject($message->getDeliveryTag(), $requeue);
    }

    /**
     * @param int $timeout
     *
     * @return InteropAmqpMessage|null
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
     * @return InteropAmqpMessage|null
     */
    private function receiveBasicConsume($timeout)
    {
        if (false == $this->consumerTag) {
            $this->context->subscribe($this, function (InteropAmqpMessage $message) {
                $this->buffer->push($message->getConsumerTag(), $message);

                return false;
            });
        }

        if ($message = $this->buffer->pop($this->consumerTag)) {
            return $message;
        }

        while (true) {
            $start = microtime(true);

            $this->context->consume($timeout);

            if ($message = $this->buffer->pop($this->consumerTag)) {
                return $message;
            }

            // is here when consumed message is not for this consumer

            // as timeout is infinite have to continue consumption, but it can overflow message buffer
            if ($timeout <= 0) {
                continue;
            }

            // compute remaining timeout and continue until time is up
            $stop = microtime(true);
            $timeout -= ($stop - $start) * 1000;

            if ($timeout <= 0) {
                break;
            }
        }
    }
}
