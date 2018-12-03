<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class SqsDestination implements Topic, Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var string|null
     */
    private $queueOwnerAWSAccountId;

    /**
     * The name of the new queue.
     * The following limits apply to this name:
     *   * A queue name can have up to 80 characters.
     *   * Valid values: alphanumeric characters, hyphens (-), and underscores (_).
     *   * A FIFO queue name must end with the .fifo suffix.
     *   * Queue names are case-sensitive.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->attributes = [];
    }

    public function getQueueName(): string
    {
        return $this->name;
    }

    public function getTopicName(): string
    {
        return $this->name;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     *  The number of seconds for which the delivery of all messages in the queue is delayed.
     *  Valid values: An integer from 0 to 900 seconds (15 minutes). The default is 0 (zero).
     */
    public function setDelaySeconds(int $seconds = null): void
    {
        if (null == $seconds) {
            unset($this->attributes['DelaySeconds']);
        } else {
            $this->attributes['DelaySeconds'] = $seconds;
        }
    }

    /**
     * The limit of how many bytes a message can contain before Amazon SQS rejects it.
     * Valid values: An integer from 1,024 bytes (1 KiB) to 262,144 bytes (256 KiB).
     * The default is 262,144 (256 KiB).
     */
    public function setMaximumMessageSize(int $bytes = null): void
    {
        if (null == $bytes) {
            unset($this->attributes['MaximumMessageSize']);
        } else {
            $this->attributes['MaximumMessageSize'] = $bytes;
        }
    }

    /**
     * The number of seconds for which Amazon SQS retains a message.
     * Valid values: An integer from 60 seconds (1 minute) to 1,209,600 seconds (14 days).
     * The default is 345,600 (4 days).
     */
    public function setMessageRetentionPeriod(int $seconds = null): void
    {
        if (null == $seconds) {
            unset($this->attributes['MessageRetentionPeriod']);
        } else {
            $this->attributes['MessageRetentionPeriod'] = $seconds;
        }
    }

    /**
     * The queue's policy. A valid AWS policy. For more information about policy structure,
     * see http://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html.
     */
    public function setPolicy(string $policy = null): void
    {
        if (null == $policy) {
            unset($this->attributes['Policy']);
        } else {
            $this->attributes['Policy'] = $policy;
        }
    }

    /**
     * The number of seconds for which a ReceiveMessage action waits for a message to arrive.
     * Valid values: An integer from 0 to 20 (seconds). The default is 0 (zero).
     */
    public function setReceiveMessageWaitTimeSeconds(int $seconds = null): void
    {
        if (null == $seconds) {
            unset($this->attributes['ReceiveMessageWaitTimeSeconds']);
        } else {
            $this->attributes['ReceiveMessageWaitTimeSeconds'] = $seconds;
        }
    }

    /**
     * The parameters for the dead letter queue functionality of the source queue.
     * For more information about the redrive policy and dead letter queues,
     * see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-dead-letter-queues.html.
     * The dead letter queue of a FIFO queue must also be a FIFO queue.
     * Similarly, the dead letter queue of a standard queue must also be a standard queue.
     */
    public function setRedrivePolicy(int $maxReceiveCount, string $deadLetterTargetArn): void
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
     */
    public function setVisibilityTimeout(int $seconds = null): void
    {
        if (null == $seconds) {
            unset($this->attributes['VisibilityTimeout']);
        } else {
            $this->attributes['VisibilityTimeout'] = $seconds;
        }
    }

    /**
     * Only FIFO.
     *
     * Designates a queue as FIFO. You can provide this attribute only during queue creation.
     * You can't change it for an existing queue. When you set this attribute, you must provide a MessageGroupId explicitly.
     * For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-understanding-logic.
     */
    public function setFifoQueue(bool $enable): void
    {
        if ($enable) {
            $this->attributes['FifoQueue'] = 'true';
        } else {
            unset($this->attributes['FifoQueue']);
        }
    }

    /**
     * Only FIFO.
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
     */
    public function setContentBasedDeduplication(bool $enable): void
    {
        if ($enable) {
            $this->attributes['ContentBasedDeduplication'] = 'true';
        } else {
            unset($this->attributes['ContentBasedDeduplication']);
        }
    }

    public function getQueueOwnerAWSAccountId(): ?string
    {
        return $this->queueOwnerAWSAccountId;
    }

    public function setQueueOwnerAWSAccountId(?string $queueOwnerAWSAccountId): void
    {
        $this->queueOwnerAWSAccountId = $queueOwnerAWSAccountId;
    }

    public function setRegion(string $region = null): void
    {
        $this->region = $region;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }
}
