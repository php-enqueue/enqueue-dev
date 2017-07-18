<?php

namespace Enqueue\Amqplib;

use Interop\Queue\PsrMessage;

class AmqpMessage implements PsrMessage
{
    private $body;
    private $properties;
    private $headers;
    private $redelivered;
    private $mandatory;
    private $immediate;
    private $ticket;
    private $deliveryTag;

    public function __construct($body = null, array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    public function setRedelivered($redelivered)
    {
        $this->redelivered = (bool) $redelivered;
    }

    public function isRedelivered()
    {
        return $this->redelivered;
    }

    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    public function getCorrelationId()
    {
        return $this->getHeader('correlation_id');
    }

    public function setMessageId($messageId)
    {
        $this->setHeader('message_id', $messageId);
    }

    public function getMessageId()
    {
        return $this->getHeader('message_id');
    }

    public function getTimestamp()
    {
        $value = $this->getHeader('timestamp');

        return $value === null ? null : (int) $value;
    }

    public function setTimestamp($timestamp)
    {
        $this->setHeader('timestamp', $timestamp);
    }

    public function setReplyTo($replyTo)
    {
        $this->setHeader('reply_to', $replyTo);
    }

    public function getReplyTo()
    {
        return $this->getHeader('reply_to');
    }

    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    public function setDeliveryTag($deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    public function isMandatory()
    {
        return $this->mandatory;
    }

    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    public function isImmediate()
    {
        return $this->immediate;
    }

    public function setImmediate($immediate)
    {
        $this->immediate = $immediate;
    }

    public function getTicket()
    {
        return $this->ticket;
    }

    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
    }
}
