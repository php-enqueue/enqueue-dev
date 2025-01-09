<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class SnsQsConsumer implements Consumer
{
    /**
     * @var SnsQsContext
     */
    private $context;

    /**
     * @var SqsConsumer
     */
    private $consumer;

    /**
     * @var SnsQsQueue
     */
    private $queue;

    public function __construct(SnsQsContext $context, SqsConsumer $consumer, SnsQsQueue $queue)
    {
        $this->context = $context;
        $this->consumer = $consumer;
        $this->queue = $queue;
    }

    public function getVisibilityTimeout(): ?int
    {
        return $this->consumer->getVisibilityTimeout();
    }

    /**
     * The duration (in seconds) that the received messages are hidden from subsequent retrieve
     * requests after being retrieved by a ReceiveMessage request.
     */
    public function setVisibilityTimeout(?int $visibilityTimeout = null): void
    {
        $this->consumer->setVisibilityTimeout($visibilityTimeout);
    }

    public function getMaxNumberOfMessages(): int
    {
        return $this->consumer->getMaxNumberOfMessages();
    }

    /**
     * The maximum number of messages to return. Amazon SQS never returns more messages than this value
     * (however, fewer messages might be returned). Valid values are 1 to 10. Default is 1.
     */
    public function setMaxNumberOfMessages(int $maxNumberOfMessages): void
    {
        $this->consumer->setMaxNumberOfMessages($maxNumberOfMessages);
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function receive(int $timeout = 0): ?Message
    {
        if ($sqsMessage = $this->consumer->receive($timeout)) {
            return $this->convertMessage($sqsMessage);
        }

        return null;
    }

    public function receiveNoWait(): ?Message
    {
        if ($sqsMessage = $this->consumer->receiveNoWait()) {
            return $this->convertMessage($sqsMessage);
        }

        return null;
    }

    /**
     * @param SnsQsMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, SnsQsMessage::class);

        $this->consumer->acknowledge($message->getSqsMessage());
    }

    /**
     * @param SnsQsMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, SnsQsMessage::class);

        $this->consumer->reject($message->getSqsMessage(), $requeue);
    }

    private function convertMessage(SqsMessage $sqsMessage): SnsQsMessage
    {
        $message = $this->context->createMessage();
        $message->setRedelivered($sqsMessage->isRedelivered());
        $message->setSqsMessage($sqsMessage);

        $body = $sqsMessage->getBody();

        if (isset($body[0]) && '{' === $body[0]) {
            $data = json_decode($sqsMessage->getBody(), true);

            if (isset($data['TopicArn']) && isset($data['Type']) && 'Notification' === $data['Type']) {
                // SNS message conversion
                if (isset($data['Message'])) {
                    $message->setBody((string) $data['Message']);
                }

                if (isset($data['MessageAttributes']['Headers'])) {
                    $headersData = json_decode($data['MessageAttributes']['Headers']['Value'], true);

                    $message->setHeaders($headersData[0]);
                    $message->setProperties($headersData[1]);
                }

                return $message;
            }
        }

        $message->setBody($sqsMessage->getBody());
        $message->setHeaders($sqsMessage->getHeaders());
        $message->setProperties($sqsMessage->getProperties());

        return $message;
    }
}
