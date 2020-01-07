<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class SqsConsumer implements Consumer
{
    /**
     * @var SqsDestination
     */
    private $queue;

    /**
     * @var SqsContext
     */
    private $context;

    /**
     * @var int|null
     */
    private $visibilityTimeout;

    /**
     * @var int
     */
    private $maxNumberOfMessages;

    /**
     * @var array
     */
    private $messages;

    public function __construct(SqsContext $context, SqsDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->messages = [];
        $this->maxNumberOfMessages = 1;
    }

    public function getVisibilityTimeout(): ?int
    {
        return $this->visibilityTimeout;
    }

    /**
     * The duration (in seconds) that the received messages are hidden from subsequent retrieve
     * requests after being retrieved by a ReceiveMessage request.
     */
    public function setVisibilityTimeout(int $visibilityTimeout = null): void
    {
        $this->visibilityTimeout = $visibilityTimeout;
    }

    public function getMaxNumberOfMessages(): int
    {
        return $this->maxNumberOfMessages;
    }

    /**
     * The maximum number of messages to return. Amazon SQS never returns more messages than this value
     * (however, fewer messages might be returned). Valid values are 1 to 10. Default is 1.
     */
    public function setMaxNumberOfMessages(int $maxNumberOfMessages): void
    {
        $this->maxNumberOfMessages = $maxNumberOfMessages;
    }

    /**
     * @return SqsDestination
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return SqsMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        $maxLongPollingTime = 20; // 20 is max allowed long polling value

        if (0 === $timeout) {
            while (true) {
                if ($message = $this->receiveMessage($maxLongPollingTime)) {
                    return $message;
                }
            }
        }

        $timeout = (int) ceil($timeout / 1000);

        if ($timeout > $maxLongPollingTime) {
            throw new \LogicException(sprintf('Max allowed SQS receive message timeout is: "%s"', $maxLongPollingTime));
        }

        return $this->receiveMessage($timeout);
    }

    /**
     * @return SqsMessage
     */
    public function receiveNoWait(): ?Message
    {
        return $this->receiveMessage(0);
    }

    /**
     * @param SqsMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, SqsMessage::class);

        $this->context->getSqsClient()->deleteMessage([
            '@region' => $this->queue->getRegion(),
            'QueueUrl' => $this->context->getQueueUrl($this->queue),
            'ReceiptHandle' => $message->getReceiptHandle(),
        ]);
    }

    /**
     * @param SqsMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, SqsMessage::class);

        if ($requeue) {
            $this->context->getSqsClient()->changeMessageVisibility([
                '@region' => $this->queue->getRegion(),
                'QueueUrl' => $this->context->getQueueUrl($this->queue),
                'ReceiptHandle' => $message->getReceiptHandle(),
                'VisibilityTimeout' => $message->getRequeueVisibilityTimeout(),
            ]);
        } else {
            $this->context->getSqsClient()->deleteMessage([
                '@region' => $this->queue->getRegion(),
                'QueueUrl' => $this->context->getQueueUrl($this->queue),
                'ReceiptHandle' => $message->getReceiptHandle(),
            ]);
        }
    }

    protected function receiveMessage(int $timeoutSeconds): ?SqsMessage
    {
        if ($message = array_pop($this->messages)) {
            return $this->convertMessage($message);
        }

        $arguments = [
            '@region' => $this->queue->getRegion(),
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => $this->maxNumberOfMessages,
            'QueueUrl' => $this->context->getQueueUrl($this->queue),
            'WaitTimeSeconds' => $timeoutSeconds,
        ];

        if ($this->visibilityTimeout) {
            $arguments['VisibilityTimeout'] = $this->visibilityTimeout;
        }

        $result = $this->context->getSqsClient()->receiveMessage($arguments);

        if ($result->hasKey('Messages')) {
            $this->messages = $result->get('Messages');
        }

        if ($message = array_pop($this->messages)) {
            return $this->convertMessage($message);
        }

        return null;
    }

    protected function convertMessage(array $sqsMessage): SqsMessage
    {
        $message = $this->context->createMessage();

        $message->setBody($sqsMessage['Body']);
        $message->setReceiptHandle($sqsMessage['ReceiptHandle']);

        if (isset($sqsMessage['Attributes'])) {
            $message->setAttributes($sqsMessage['Attributes']);
        }

        if (isset($sqsMessage['Attributes']['ApproximateReceiveCount'])) {
            $message->setRedelivered(((int) $sqsMessage['Attributes']['ApproximateReceiveCount']) > 1);
        }

        if (isset($sqsMessage['MessageAttributes']['Headers'])) {
            $headers = json_decode($sqsMessage['MessageAttributes']['Headers']['StringValue'], true);

            $message->setHeaders($headers[0]);
            $message->setProperties($headers[1]);
        }

        $message->setMessageId($sqsMessage['MessageId']);

        return $message;
    }
}
