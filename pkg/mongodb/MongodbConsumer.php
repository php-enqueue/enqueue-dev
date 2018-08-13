<?php

namespace Enqueue\Mongodb;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

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
    private $pollingInterval = 1000000;

    /**
     * @param MongodbContext     $context
     * @param MongodbDestination $queue
     */
    public function __construct(MongodbContext $context, MongodbDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * Set polling interval in milliseconds.
     *
     * @param int $msec
     */
    public function setPollingInterval($msec)
    {
        $this->pollingInterval = $msec * 1000;
    }

    /**
     * Get polling interval in milliseconds.
     *
     * @return int
     */
    public function getPollingInterval()
    {
        return (int) $this->pollingInterval / 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @return MongodbDestination
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return MongodbMessage|null
     */
    public function receive($timeout = 0)
    {
        $timeout /= 1000;
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveMessage();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return;
            }

            usleep($this->pollingInterval);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return MongodbMessage|null
     */
    public function receiveNoWait()
    {
        return $this->receiveMessage();
    }

    /**
     * {@inheritdoc}
     *
     * @param MongodbMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        // does nothing
    }

    /**
     * {@inheritdoc}
     *
     * @param MongodbMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, MongodbMessage::class);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }
    }

    /**
     * @return MongodbMessage|null
     */
    protected function receiveMessage()
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
    }

    /**
     * @param array $dbalMessage
     *
     * @return MongodbMessage
     */
    protected function convertMessage(array $mongodbMessage)
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
