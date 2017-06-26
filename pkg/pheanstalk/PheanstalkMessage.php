<?php

namespace Enqueue\Pheanstalk;

use Enqueue\Psr\PsrMessage;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

class PheanstalkMessage implements PsrMessage, \JsonSerializable
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
     * @var Job
     */
    private $job;

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

        return $value === null ? null : (int) $value;
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
     * @param int $time
     */
    public function setTimeToRun($time)
    {
        $this->setHeader('ttr', $time);
    }

    /**
     * @return int
     */
    public function getTimeToRun()
    {
        return $this->getHeader('ttr', Pheanstalk::DEFAULT_TTR);
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->setHeader('priority', $priority);
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->getHeader('priority', Pheanstalk::DEFAULT_PRIORITY);
    }

    /**
     * @param int $delay
     */
    public function setDelay($delay)
    {
        $this->setHeader('delay', $delay);
    }

    /**
     * @return int
     */
    public function getDelay()
    {
        return $this->getHeader('delay', Pheanstalk::DEFAULT_DELAY);
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
     * @return PheanstalkMessage
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
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job)
    {
        $this->job = $job;
    }
}
