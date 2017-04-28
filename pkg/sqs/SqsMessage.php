<?php
namespace Enqueue\Sqs;

use Enqueue\Psr\PsrMessage;

class SqsMessage implements PsrMessage
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
     * @var boolean
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
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     */
    public function __construct($body = null, array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
        $this->delaySeconds = 0;
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
     * {@inheritdoc}
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
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
    public function getProperties()
    {
        return $this->properties;
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
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ?$this->headers[$name] : $default;
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
    public function setRedelivered($redelivered)
    {
        $this->redelivered = $redelivered;
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
    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId()
    {
        return $this->getHeader('correlation_id', '');
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
        return $this->getHeader('message_id', '');
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
        $this->setHeader('timestamp', (int) $timestamp);
    }

    /**
     * The number of seconds to delay a specific message. Valid values: 0 to 900. Maximum: 15 minutes.
     * Messages with a positive DelaySeconds value become available for processing after the delay period is finished.
     * If you don't specify a value, the default value for the queue applies.
     * When you set FifoQueue, you can't set DelaySeconds per message. You can set this parameter only on a queue level.
     *
     * Set delay in seconds
     *
     * @param int $seconds
     */
    public function setDelaySeconds($seconds)
    {
        $this->delaySeconds = (int) $seconds;
    }

    /**
     * @return int
     */
    public function getDelaySeconds()
    {
        return $this->delaySeconds;
    }

    /**
     * Only FIFO
     *
     * The token used for deduplication of sent messages. If a message with a particular MessageDeduplicationId is sent successfully,
     * any messages sent with the same MessageDeduplicationId are accepted successfully but aren't delivered during the 5-minute
     * deduplication interval. For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-exactly-once-processing.
     *
     * @param string|null $id
     */
    public function setMessageDeduplicationId($id)
    {
        $this->messageDeduplicationId = $id;
    }

    /**
     * @return string|null
     */
    public function getMessageDeduplicationId()
    {
        return $this->messageDeduplicationId;
    }

    /**
     * Only FIFO
     *
     * The tag that specifies that a message belongs to a specific message group. Messages that belong to the same message group
     * are processed in a FIFO manner (however, messages in different message groups might be processed out of order).
     * To interleave multiple ordered streams within a single queue, use MessageGroupId values (for example, session data
     * for multiple users). In this scenario, multiple readers can process the queue, but the session data
     * of each user is processed in a FIFO fashion.
     *
     * @param string|null $id
     */
    public function setMessageGroupId($id)
    {
        $this->messageGroupId = $id;
    }

    /**
     * @return string|null
     */
    public function getMessageGroupId()
    {
        return $this->messageGroupId;
    }

    /**
     * This handle is associated with the action of receiving the message, not with the message itself.
     * To delete the message or to change the message visibility, you must provide the receipt handle (not the message ID).
     *
     * If you receive a message more than once, each time you receive it, you get a different receipt handle.
     * You must provide the most recently received receipt handle when you request to delete the message (otherwise, the message might not be deleted).
     *
     * @param string $receipt
     */
    public function setReceiptHandle($receipt)
    {
        $this->receiptHandle = $receipt;
    }

    /**
     * @return string
     */
    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }
}
