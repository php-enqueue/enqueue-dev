<?php

namespace Enqueue\AmqpExt;

use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrMessageTrait;

class AmqpMessage implements PsrMessage
{
    use PsrMessageTrait;

    /**
     * @var string|null
     */
    private $deliveryTag;

    /**
     * @var string|null
     */
    private $consumerTag;

    /**
     * @var int
     */
    private $flags;

    /**
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     */
    public function __construct($body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;

        $this->redelivered = false;
        $this->flags = AMQP_NOPARAM;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId()
    {
        return $this->getHeader('correlation_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageId($messageId)
    {
        $this->setHeader('message_id', $messageId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId()
    {
        return $this->getHeader('message_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp()
    {
        return $this->getHeader('timestamp');
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp($timestamp)
    {
        $this->setHeader('timestamp', $timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function setReplyTo($replyTo)
    {
        $this->setHeader('reply_to', $replyTo);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplyTo()
    {
        return $this->getHeader('reply_to');
    }

    /**
     * @return null|string
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * @param null|string $deliveryTag
     */
    public function setDeliveryTag($deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * @return string|null
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @param string|null $consumerTag
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    public function clearFlags()
    {
        $this->flags = AMQP_NOPARAM;
    }

    /**
     * @param int $flag
     */
    public function addFlag($flag)
    {
        $this->flags = $this->flags | $flag;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }
}
