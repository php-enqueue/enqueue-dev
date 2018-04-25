<?php

namespace Enqueue\Gps;

use Google\Cloud\PubSub\Message;
use Interop\Queue\PsrMessage;

class GpsMessage implements PsrMessage, \JsonSerializable
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
     * @var Message
     */
    private $nativeMessage;

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
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedelivered($redelivered)
    {
        $this->redelivered = (bool) $redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedelivered()
    {
        return $this->redelivered;
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
        $value = $this->getHeader('timestamp');

        return null === $value ? null : (int) $value;
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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'body' => $this->getBody(),
            'properties' => $this->getProperties(),
            'headers' => $this->getHeaders(),
        ];
    }

    /**
     * @param string $json
     *
     * @return GpsMessage
     */
    public static function jsonUnserialize($json)
    {
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return new self($data['body'], $data['properties'], $data['headers']);
    }

    /**
     * @return Message
     */
    public function getNativeMessage()
    {
        return $this->nativeMessage;
    }

    /**
     * @param Message $message
     */
    public function setNativeMessage(Message $message)
    {
        $this->nativeMessage = $message;
    }
}
