<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;

class MongodbConsumer implements PsrConsumer
{
    /**
     * @var MongodbContext
     */
    private $context;

    /**
     * @var MongodbDestination
     */
    private $queue;

    /**
     * @var int microseconds
     */
    private $pollingInterval;

    public function __construct(MongodbContext $context, MongodbDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;

        $this->pollingInterval = 1000;
    }

    /**
     * Set polling interval in milliseconds.
     */
    public function setPollingInterval(int $msec): void
    {
        $this->pollingInterval = $msec;
    }

    /**
     * Get polling interval in milliseconds.
     */
    public function getPollingInterval(): int
    {
        return $this->pollingInterval;
    }

    /**
     * @return MongodbDestination
     */
    public function getQueue(): PsrQueue
    {
        return $this->queue;
    }

    /**
     * @return MongodbMessage
     */
    public function receive(int $timeout = 0): ?PsrMessage
    {
        $timeout /= 1000;
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveMessage();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }

            usleep($this->pollingInterval * 1000);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }
        }
    }

    /**
     * @return MongodbMessage
     */
    public function receiveNoWait(): ?PsrMessage
    {
        return $this->receiveMessage();
    }

    /**
     * @param MongodbMessage $message
     */
    public function acknowledge(PsrMessage $message): void
    {
        // does nothing
    }

    /**
     * @param MongodbMessage $message
     */
    public function reject(PsrMessage $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, MongodbMessage::class);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }
    }

    protected function receiveMessage(): ?MongodbMessage
    {
        $now = time();
        $collection = $this->context->getCollection();
        $message = $collection->findOneAndDelete(
            [
                'queue' => $this->queue->getName(),
                '$or' => [
                    ['delayed_until' => ['$exists' => false]],
                    ['delayed_until' => ['$lte' => $now]],
                ],
            ],
            [
                'sort' => ['priority' => -1, 'published_at' => 1],
                'typeMap' => ['root' => 'array', 'document' => 'array'],
            ]
        );

        if (!$message) {
            return null;
        }
        if (empty($message['time_to_live']) || $message['time_to_live'] > time()) {
            return $this->convertMessage($message);
        }

        return null;
    }

    protected function convertMessage(array $mongodbMessage): MongodbMessage
    {
        $properties = JSON::decode($mongodbMessage['properties']);
        $headers = JSON::decode($mongodbMessage['headers']);

        $message = $this->context->createMessage($mongodbMessage['body'], $properties, $headers);
        $message->setId((string) $mongodbMessage['_id']);
        $message->setPriority((int) $mongodbMessage['priority']);
        $message->setRedelivered((bool) $mongodbMessage['redelivered']);
        $message->setPublishedAt((int) $mongodbMessage['published_at']);

        return $message;
    }
}
