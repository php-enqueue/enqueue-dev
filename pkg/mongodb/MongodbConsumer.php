<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class MongodbConsumer implements Consumer
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
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return MongodbMessage
     */
    public function receive(int $timeout = 0): ?Message
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
    public function receiveNoWait(): ?Message
    {
        return $this->receiveMessage();
    }

    /**
     * @param MongodbMessage $message
     */
    public function acknowledge(Message $message): void
    {
        // does nothing
    }

    /**
     * @param MongodbMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, MongodbMessage::class);

        if ($requeue) {
            $message->setRedelivered(true);
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }
    }

    private function receiveMessage(): ?MongodbMessage
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
            return $this->context->convertMessage($message);
        }

        return null;
    }
}
