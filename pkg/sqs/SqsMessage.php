<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Interop\Queue\Message;

class SqsMessage implements Message
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
     * @var array
     */
    private $attributes;

    /**
     * @var bool
     */
    private $redelivered;

    /**
     * @var int
     */
    private $delaySeconds;

    /**
     * @var string
     */
    private $messageDeduplicationId;

    /**
     * @var string
     */
    private $messageGroupId;

    /**
     * @var string
     */
    private $receiptHandle;

    /**
     * @var int
     */
    private $requeueVisibilityTimeout;

    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->attributes = [];
        $this->redelivered = false;
        $this->delaySeconds = 0;
        $this->requeueVisibilityTimeout = 0;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = $redelivered;
    }

    public function setReplyTo(string $replyTo = null): void
    {
        $this->setHeader('reply_to', $replyTo);
    }

    public function getReplyTo(): ?string
    {
        return $this->getHeader('reply_to');
    }

    public function setCorrelationId(string $correlationId = null): void
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getHeader('correlation_id');
    }

    public function setMessageId(string $messageId = null): void
    {
        $this->setHeader('message_id', $messageId);
    }

    public function getMessageId(): ?string
    {
        return $this->getHeader('message_id');
    }

    public function getTimestamp(): ?int
    {
        $value = $this->getHeader('timestamp');

        return null === $value ? null : (int) $value;
    }

    public function setTimestamp(int $timestamp = null): void
    {
        $this->setHeader('timestamp', $timestamp);
    }

    /**
     * The number of seconds to delay a specific message. Valid values: 0 to 900. Maximum: 15 minutes.
     * Messages with a positive DelaySeconds value become available for processing after the delay period is finished.
     * If you don't specify a value, the default value for the queue applies.
     * When you set FifoQueue, you can't set DelaySeconds per message. You can set this parameter only on a queue level.
     *
     * Set delay in seconds
     */
    public function setDelaySeconds(int $seconds): void
    {
        $this->delaySeconds = $seconds;
    }

    public function getDelaySeconds(): int
    {
        return $this->delaySeconds;
    }

    /**
     * Only FIFO.
     *
     * The token used for deduplication of sent messages. If a message with a particular MessageDeduplicationId is sent successfully,
     * any messages sent with the same MessageDeduplicationId are accepted successfully but aren't delivered during the 5-minute
     * deduplication interval. For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-exactly-once-processing.
     */
    public function setMessageDeduplicationId(string $id = null): void
    {
        $this->messageDeduplicationId = $id;
    }

    public function getMessageDeduplicationId(): ?string
    {
        return $this->messageDeduplicationId;
    }

    /**
     * Only FIFO.
     *
     * The tag that specifies that a message belongs to a specific message group. Messages that belong to the same message group
     * are processed in a FIFO manner (however, messages in different message groups might be processed out of order).
     * To interleave multiple ordered streams within a single queue, use MessageGroupId values (for example, session data
     * for multiple users). In this scenario, multiple readers can process the queue, but the session data
     * of each user is processed in a FIFO fashion.
     */
    public function setMessageGroupId(string $id = null): void
    {
        $this->messageGroupId = $id;
    }

    public function getMessageGroupId(): ?string
    {
        return $this->messageGroupId;
    }

    /**
     * This handle is associated with the action of receiving the message, not with the message itself.
     * To delete the message or to change the message visibility, you must provide the receipt handle (not the message ID).
     *
     * If you receive a message more than once, each time you receive it, you get a different receipt handle.
     * You must provide the most recently received receipt handle when you request to delete the message (otherwise, the message might not be deleted).
     */
    public function setReceiptHandle(string $receipt = null): void
    {
        $this->receiptHandle = $receipt;
    }

    public function getReceiptHandle(): ?string
    {
        return $this->receiptHandle;
    }

    /**
     * The number of seconds before the message can be visible again when requeuing. Valid values: 0 to 43200. Maximum: 12 hours.
     *
     * Set requeue visibility timeout
     */
    public function setRequeueVisibilityTimeout(int $seconds): void
    {
        $this->requeueVisibilityTimeout = $seconds;
    }

    public function getRequeueVisibilityTimeout(): int
    {
        return $this->requeueVisibilityTimeout;
    }
}
