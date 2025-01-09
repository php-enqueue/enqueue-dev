<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class SnsQsMessage implements Message
{
    use MessageTrait;

    /**
     * @var SqsMessage
     */
    private $sqsMessage;

    /**
     * @var array|null
     */
    private $messageAttributes;

    /**
     * @var string|null
     */
    private $messageGroupId;

    /**
     * @var string|null
     */
    private $messageDeduplicationId;

    /**
     * See AWS documentation for message attribute structure.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#shape-messageattributevalue
     */
    public function __construct(
        string $body = '',
        array $properties = [],
        array $headers = [],
        ?array $messageAttributes = null
    ) {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
        $this->messageAttributes = $messageAttributes;
    }

    public function setSqsMessage(SqsMessage $message): void
    {
        $this->sqsMessage = $message;
    }

    public function getSqsMessage(): SqsMessage
    {
        return $this->sqsMessage;
    }

    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    public function setMessageAttributes(?array $messageAttributes): void
    {
        $this->messageAttributes = $messageAttributes;
    }

    /**
     * Only FIFO.
     *
     * The token used for deduplication of sent messages. If a message with a particular MessageDeduplicationId is sent successfully,
     * any messages sent with the same MessageDeduplicationId are accepted successfully but aren't delivered during the 5-minute
     * deduplication interval. For more information, see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html#FIFO-queues-exactly-once-processing.
     */
    public function setMessageDeduplicationId(?string $id = null): void
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
    public function setMessageGroupId(?string $id = null): void
    {
        $this->messageGroupId = $id;
    }

    public function getMessageGroupId(): ?string
    {
        return $this->messageGroupId;
    }
}
