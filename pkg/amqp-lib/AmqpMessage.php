<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\PsrMessage;

class AmqpMessage implements PsrMessage
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var bool
     */
    private $redelivered;

    /**
     * @var bool
     */
    private $mandatory;

    /**
     * @var bool
     */
    private $immediate;

    /**
     * @var int
     */
    private $ticket;

    /**
     * @var string
     */
    private $deliveryTag;

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
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    /**
     * @param bool $redelivered
     */
    public function setRedelivered($redelivered)
    {
        $this->redelivered = (bool) $redelivered;
    }

    /**
     * @return bool
     */
    public function isRedelivered()
    {
        return $this->redelivered;
    }

    /**
     * @param string $correlationId
     */
    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    /**
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->getHeader('correlation_id');
    }

    /**
     * @param string $messageId
     */
    public function setMessageId($messageId)
    {
        $this->setHeader('message_id', $messageId);
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->getHeader('message_id');
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        $value = $this->getHeader('timestamp');

        return $value === null ? null : (int) $value;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->setHeader('timestamp', $timestamp);
    }

    /**
     * @param string|null $replyTo
     */
    public function setReplyTo($replyTo)
    {
        $this->setHeader('reply_to', $replyTo);
    }

    /**
     * @return string|null
     */
    public function getReplyTo()
    {
        return $this->getHeader('reply_to');
    }

    /**
     * @return string
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * @param string $deliveryTag
     */
    public function setDeliveryTag($deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param int $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @return bool
     */
    public function isImmediate()
    {
        return $this->immediate;
    }

    /**
     * @param bool $immediate
     */
    public function setImmediate($immediate)
    {
        $this->immediate = $immediate;
    }

    /**
     * @return int
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param int $ticket
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
    }
}
