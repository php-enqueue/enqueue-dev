<?php

namespace Enqueue\Client;

class Message
{
    /**
     * @const string
     */
    const SCOPE_MESSAGE_BUS = 'enqueue.scope.message_bus';

    /**
     * @const string
     */
    const SCOPE_APP = 'enqueue.scope.app';

    /**
     * @var string|null
     */
    private $body;

    /**
     * @var string|null
     */
    private $contentType;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string
     */
    private $priority;

    /**
     * The number of seconds the message should be removed from the queue without processing.
     *
     * @var int|null
     */
    private $expire;

    /**
     * The number of seconds the message should be delayed before it will be send to a queue.
     *
     * @var int|null
     */
    private $delay;

    /**
     * @var string
     */
    private $replyTo;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @var string
     */
    private $scope;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $properties = [];

    public function __construct($body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->properties = $properties;

        $this->scope = static::SCOPE_MESSAGE_BUS;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string|int|float|array|\JsonSerializable|null $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string|null $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Gets the number of seconds the message should be removed from the queue without processing.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @param int|null $expire
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;
    }

    /**
     * Gets the number of seconds the message should be delayed before it will be send to a queue.
     *
     * @return int|null
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set delay in seconds.
     *
     * @param int|null $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param string $replyTo
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;
    }

    /**
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    /**
     * @param string $correlationId
     */
    public function setCorrelationId($correlationId)
    {
        $this->correlationId = $correlationId;
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
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
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
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
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
     * @param string $name
     * @param mixed  $value
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }
}
