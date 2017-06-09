<?php

namespace Enqueue\AmqpExt;

use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;

class AmqpConsumer implements PsrConsumer
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @var Buffer
     */
    private $buffer;

    /**
     * @var \AMQPQueue
     */
    private $extQueue;

    /**
     * @var bool
     */
    private $isInit;

    /**
     * @var string
     */
    private $receiveMethod;

    /**
     * @param AmqpContext $context
     * @param AmqpQueue   $queue
     * @param Buffer      $buffer
     * @param string      $receiveMethod
     */
    public function __construct(AmqpContext $context, AmqpQueue $queue, Buffer $buffer, $receiveMethod)
    {
        $this->queue = $queue;
        $this->context = $context;
        $this->buffer = $buffer;
        $this->receiveMethod = $receiveMethod;

        $this->isInit = false;
    }

    /**
     * {@inheritdoc}
     *
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
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receiveNoWait()
    {
        if ($extMessage = $this->getExtQueue()->get()) {
            return $this->convertMessage($extMessage);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->getExtQueue()->ack($message->getDeliveryTag());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->getExtQueue()->reject(
            $message->getDeliveryTag(),
            $requeue ? AMQP_REQUEUE : AMQP_NOPARAM
        );
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
        }
    }

    /**
     * @param int $timeout
     *
     * @return AmqpMessage|null
     */
    private function receiveBasicConsume($timeout)
    {
        if ($this->isInit && $message = $this->buffer->pop($this->getExtQueue()->getConsumerTag())) {
            return $message;
        }

        /** @var \AMQPQueue $extQueue */
        $extConnection = $this->getExtQueue()->getChannel()->getConnection();

        $originalTimeout = $extConnection->getReadTimeout();
        try {
            $extConnection->setReadTimeout($timeout / 1000);

            if (false == $this->isInit) {
                $this->getExtQueue()->consume(null, AMQP_NOPARAM);

                $this->isInit = true;
            }

            /** @var AmqpMessage|null $message */
            $message = null;

            $this->getExtQueue()->consume(function (\AMQPEnvelope $extEnvelope, \AMQPQueue $q) use (&$message) {
                $message = $this->convertMessage($extEnvelope);
                $message->setConsumerTag($q->getConsumerTag());

                if ($this->getExtQueue()->getConsumerTag() == $q->getConsumerTag()) {
                    return false;
                }

                // not our message, put it to buffer and continue.
                $this->buffer->push($q->getConsumerTag(), $message);

                $message = null;

                return true;
            }, AMQP_JUST_CONSUME);

            return $message;
        } catch (\AMQPQueueException $e) {
            if ('Consumer timeout exceed' == $e->getMessage()) {
                return null;
            }

            throw $e;
        } finally {
            $extConnection->setReadTimeout($originalTimeout);
        }
    }

    /**
     * @param \AMQPEnvelope $extEnvelope
     *
     * @return AmqpMessage
     */
    private function convertMessage(\AMQPEnvelope $extEnvelope)
    {
        $message = new AmqpMessage(
            $extEnvelope->getBody(),
            $extEnvelope->getHeaders(),
            [
                'message_id' => $extEnvelope->getMessageId(),
                'correlation_id' => $extEnvelope->getCorrelationId(),
                'app_id' => $extEnvelope->getAppId(),
                'type' => $extEnvelope->getType(),
                'content_encoding' => $extEnvelope->getContentEncoding(),
                'content_type' => $extEnvelope->getContentType(),
                'expiration' => $extEnvelope->getExpiration(),
                'priority' => $extEnvelope->getPriority(),
                'reply_to' => $extEnvelope->getReplyTo(),
                'timestamp' => $extEnvelope->getTimeStamp(),
                'user_id' => $extEnvelope->getUserId(),
            ]
        );
        $message->setRedelivered($extEnvelope->isRedelivery());
        $message->setDeliveryTag($extEnvelope->getDeliveryTag());

        return $message;
    }

    /**
     * @return \AMQPQueue
     */
    private function getExtQueue()
    {
        if (false == $this->extQueue) {
            $extQueue = new \AMQPQueue($this->context->getExtChannel());
            $extQueue->setName($this->queue->getQueueName());
            $extQueue->setFlags($this->queue->getFlags());
            $extQueue->setArguments($this->queue->getArguments());

            $this->extQueue = $extQueue;
        }

        return $this->extQueue;
    }
}
