<?php

declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Consumer;
use Interop\Queue\ConsumerWithInterval;
use Interop\Queue\ConsumerWithIntervalTrait;
use Interop\Queue\ConsumerWithVisibilityTimeout;
use Interop\Queue\ConsumerWithVisibilityTimeoutTrait;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageConsumer implements Consumer, ConsumerWithInterval, ConsumerWithVisibilityTimeout
{
    use ConsumerWithIntervalTrait;
    use ConsumerWithVisibilityTimeoutTrait;

    /**
     * @var QueueRestProxy
     */
    protected $client;

    protected $queue;

    public function __construct(QueueRestProxy $client, AzureStorageDestination $queue)
    {
        $this->client = $client;
        $this->queue = $queue;
    }

    /**
     * @inheritdoc
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @inheritdoc
     */
    public function receiveNoWait(): ?Message
    {
        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);
        $options->setVisibilityTimeoutInSeconds($this->visibilityTimeout);

        $listMessagesResult = $this->client->listMessages($this->queue->getQueueName(), $options);
        $messages = $listMessagesResult->getQueueMessages();

        if($messages) {
            $message = $messages[0];

            $formattedMessage = new AzureStorageMessage();
            $formattedMessage->setMessageId($message->getMessageId());
            $formattedMessage->setBody($message->getMessageText());
            $formattedMessage->setTimestamp($message->getInsertionDate()->getTimestamp());
            $formattedMessage->setRedelivered($message->getDequeueCount() > 1);

            $formattedMessage->setHeaders([
                'dequeue_count' => $message->getDequeueCount(),
                'expiration_date' => $message->getExpirationDate(),
                'pop_peceipt' => $message->getExpirationDate(),
                'next_time_visible' => $message->getTimeNextVisible(),
            ]);

            return $formattedMessage;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        $this->client->deleteMessage($this->queue->getQueueName(), $message->getMessageId(), $message->getHeader('pop_receipt'));
    }

    /**
     * @inheritdoc
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        if(false === $requeue) {
            $this->acknowledge($message);
        }
    }
}