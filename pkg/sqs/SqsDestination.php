<?php
namespace Enqueue\Sqs;

use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;

class SqsDestination implements PsrTopic, PsrQueue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $attributes;

    /**
     * The name of the new queue.
     * The following limits apply to this name:
     *   * A queue name can have up to 80 characters.
     *   * Valid values: alphanumeric characters, hyphens (-), and underscores (_).
     *   * A FIFO queue name must end with the .fifo suffix.
     *   * Queue names are case-sensitive.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->name;
    }

    /**
     *  The number of seconds for which the delivery of all messages in the queue is delayed.
     *  Valid values: An integer from 0 to 900 seconds (15 minutes). The default is 0 (zero).
     *
     * @param int $seconds
     */
    public function setDelaySeconds($seconds)
    {
        $this->attributes['DelaySeconds'] = (int) $seconds;
    }

    /**
     * The limit of how many bytes a message can contain before Amazon SQS rejects it.
     * Valid values: An integer from 1,024 bytes (1 KiB) to 262,144 bytes (256 KiB).
     * The default is 262,144 (256 KiB).
     *
     * @param int $bytes
     */
    public function setMaximumMessageSize($bytes)
    {
        $this->attributes['MaximumMessageSize'] = (int) $bytes;
    }

    /**
     * The number of seconds for which Amazon SQS retains a message.
     * Valid values: An integer from 60 seconds (1 minute) to 1,209,600 seconds (14 days).
     * The default is 345,600 (4 days).
     *
     * @param int $seconds
     */
    public function setMessageRetentionPeriod($seconds)
    {
        $this->attributes['MessageRetentionPeriod'] = (int) $seconds;
    }

    /**
     * The queue's policy. A valid AWS policy. For more information about policy structure,
     * see http://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html.
     *
     * @param string $policy
     */
    public function setPolicy($policy)
    {
        $this->attributes['Policy'] = $policy;
    }

    /**
     * The number of seconds for which a ReceiveMessage action waits for a message to arrive.
     * Valid values: An integer from 0 to 20 (seconds). The default is 0 (zero).
     *
     * @param int $seconds
     */
    public function setReceiveMessageWaitTimeSeconds($seconds)
    {
        $this->attributes['ReceiveMessageWaitTimeSeconds'] = (int) $seconds;
    }

    /**
     * The parameters for the dead letter queue functionality of the source queue.
     * For more information about the redrive policy and dead letter queues,
     * see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-dead-letter-queues.html.
     * The dead letter queue of a FIFO queue must also be a FIFO queue.
     * Similarly, the dead letter queue of a standard queue must also be a standard queue.
     *
     * @param int    $maxReceiveCount
     * @param string $deadLetterTargetArn
     */
    public function setRedrivePolicy($maxReceiveCount, $deadLetterTargetArn)
    {
        $this->attributes['RedrivePolicy'] = json_encode([
            'maxReceiveCount' => (string) $maxReceiveCount,
            'deadLetterTargetArn' => (string) $deadLetterTargetArn,
        ]);
    }

    /**
     * The visibility timeout for the queue. Valid values: An integer from 0 to 43,200 (12 hours).
     * The default is 30. For more information about the visibility timeout,
     * see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-visibility-timeout.html.
     *
     * @param int $seconds
     */
    public function setVisibilityTimeout($seconds)
    {
        $this->attributes['VisibilityTimeout'] = (int) $seconds;
    }

    /**
     * Only FIFO
     *
     * Designates a queue as FIFO. You can provide this attribute only during queue creation.
     * You can't change it for an existing queue. When you set this attribute, you must provide a MessageGroupId explicitly.
     * For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-understanding-logic.
     *
     * @param bool $enable
     */
    public function setFifoQueue($enable)
    {
        if ($enable) {
            $this->attributes['FifoQueue'] = 'true';
        } else {
            unset($this->attributes['FifoQueue']);
        }
    }

    /**
     * Only FIFO
     *
     *  Enables content-based deduplication.
     *  For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-exactly-once-processing.
     *   * Every message must have a unique MessageDeduplicationId,
     *     * You may provide a MessageDeduplicationId explicitly.
     *     * If you aren't able to provide a MessageDeduplicationId and you enable ContentBasedDeduplication for your queue,
     *       Amazon SQS uses a SHA-256 hash to generate the MessageDeduplicationId using the body of the message (but not the attributes of the message).
     *     * If you don't provide a MessageDeduplicationId and the queue doesn't have ContentBasedDeduplication set,
     *       the action fails with an error.
     *     * If the queue has ContentBasedDeduplication set, your MessageDeduplicationId overrides the generated one.
     *   * When ContentBasedDeduplication is in effect, messages with identical content sent within the deduplication
     *     interval are treated as duplicates and only one copy of the message is delivered.
     *   * You can also use ContentBasedDeduplication for messages with identical content to be treated as duplicates.
     *   * If you send one message with ContentBasedDeduplication enabled and then another message with a MessageDeduplicationId
     *     that is the same as the one generated for the first MessageDeduplicationId, the two messages are treated as
     *     duplicates and only one copy of the message is delivered.
     *
     * @param bool $enable
     */
    public function setContentBasedDeduplication($enable)
    {
        if ($enable) {
            $this->attributes['ContentBasedDeduplication'] = 'true';
        } else {
            unset($this->attributes['ContentBasedDeduplication']);
        }
    }
}
