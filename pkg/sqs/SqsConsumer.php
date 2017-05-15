<?php

namespace Enqueue\Sqs;

use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;

class SqsConsumer implements PsrConsumer
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

    /**
     * @param SqsContext     $context
     * @param SqsDestination $queue
     */
    public function __construct(SqsContext $context, SqsDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->messages = [];
        $this->maxNumberOfMessages = 1;
    }

    /**
     * @return int|null
     */
    public function getVisibilityTimeout()
    {
        return $this->visibilityTimeout;
    }

    /**
     * The duration (in seconds) that the received messages are hidden from subsequent retrieve
     * requests after being retrieved by a ReceiveMessage request.
     *
     * @param int|null $visibilityTimeout
     */
    public function setVisibilityTimeout($visibilityTimeout)
    {
        $this->visibilityTimeout = null === $visibilityTimeout ? null : (int) $visibilityTimeout;
    }

    /**
     * @return int
     */
    public function getMaxNumberOfMessages()
    {
        return $this->maxNumberOfMessages;
    }

    /**
     * The maximum number of messages to return. Amazon SQS never returns more messages than this value
     * (however, fewer messages might be returned). Valid values are 1 to 10. Default is 1.
     *
     * @param int $maxNumberOfMessages
     */
    public function setMaxNumberOfMessages($maxNumberOfMessages)
    {
        $this->maxNumberOfMessages = (int) $maxNumberOfMessages;
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsDestination
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function receive($timeout = 0)
    {
        $timeout /= 1000;

        return $this->receiveMessage($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        return $this->receiveMessage(0);
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, SqsMessage::class);

        $this->context->getClient()->deleteMessage([
            'QueueUrl' => $this->context->getQueueUrl($this->queue),
            'ReceiptHandle' => $message->getReceiptHandle(),
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, SqsMessage::class);

        $this->context->getClient()->deleteMessage([
            'QueueUrl' => $this->context->getQueueUrl($this->queue),
            'ReceiptHandle' => $message->getReceiptHandle(),
        ]);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);
        }
    }

    /**
     * @param int $timeoutSeconds
     *
     * @return SqsMessage|null
     */
    protected function receiveMessage($timeoutSeconds)
    {
        if ($message = array_pop($this->messages)) {
            return $this->convertMessage($message);
        }

        $arguments = [
            'AttributeNames' => ['All'],
            'MessageAttributeNames' => ['All'],
            'MaxNumberOfMessages' => $this->maxNumberOfMessages,
            'QueueUrl' => $this->context->getQueueUrl($this->queue),
            'WaitTimeSeconds' => $timeoutSeconds,
        ];

        if ($this->visibilityTimeout) {
            $arguments['VisibilityTimeout'] = $this->visibilityTimeout;
        }

        $result = $this->context->getClient()->receiveMessage($arguments);

        if ($result->hasKey('Messages')) {
            $this->messages = $result->get('Messages');
        }

        if ($message = array_pop($this->messages)) {
            return $this->convertMessage($message);
        }
    }

    /**
     * @param array $sqsMessage
     *
     * @return SqsMessage
     */
    protected function convertMessage(array $sqsMessage)
    {
        $message = $this->context->createMessage();

        $message->setBody($sqsMessage['Body']);
        $message->setReceiptHandle($sqsMessage['ReceiptHandle']);

        if (isset($sqsMessage['Attributes']['ApproximateReceiveCount'])) {
            $message->setRedelivered(((int) $sqsMessage['Attributes']['ApproximateReceiveCount']) > 1);
        }

        if (isset($sqsMessage['MessageAttributes']['Headers'])) {
            $headers = json_decode($sqsMessage['MessageAttributes']['Headers']['StringValue'], true);

            $message->setHeaders($headers[0]);
            $message->setProperties($headers[1]);
        }

        return $message;
    }
}
