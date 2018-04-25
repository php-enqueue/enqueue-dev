<?php

namespace Enqueue\RdKafka;

use Interop\Queue\PsrMessage;
use RdKafka\Message;

/**
 * TODO: \JsonSerializable will be removed in next version (probably 0.8.x)
 * The serialization logic was moved to JsonSerializer.
 */
class RdKafkaMessage implements PsrMessage, \JsonSerializable
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
     * @var int
     */
    private $partition;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var Message
     */
    private $kafkaMessage;

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
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $properties
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
     * @param array $headers
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
     * @return bool
     */
    public function isRedelivered()
    {
        return $this->redelivered;
    }

    /**
     * @param bool $redelivered
     */
    public function setRedelivered($redelivered)
    {
        $this->redelivered = $redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', (string) $correlationId);
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
        $this->setHeader('message_id', (string) $messageId);
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
     * @return int
     */
    public function getPartition()
    {
        return $this->partition;
    }

    /**
     * @param int $partition
     */
    public function setPartition($partition)
    {
        $this->partition = $partition;
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return Message
     */
    public function getKafkaMessage()
    {
        return $this->kafkaMessage;
    }

    /**
     * @param Message $message
     */
    public function setKafkaMessage(Message $message)
    {
        $this->kafkaMessage = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return (new JsonSerializer())->toString($this);
    }

    /**
     * @param string $json
     *
     * @return self
     */
    public static function jsonUnserialize($json)
    {
        return (new JsonSerializer())->toMessage($json);
    }
}
