<?php

namespace Enqueue\Mongodb;

use Enqueue\Util\JSON;
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
        try {
            $now = time();
            $collection = $this->context->getCollection();
            $message = $collection->findOne(['$or' => [['delayed_until' => ['$exists' => false]], ['delayed_until' => ['$lte' => $now]]]], ['sort' => ['priority' => -1]]);
            if (!$message) {
                return null;
            }
            $mongodbMessage = $message->getArrayCopy();
            $convertedMessage = $this->convertMessage($mongodbMessage);
            $affected = $collection->deleteOne(['_id' => $mongodbMessage['_id']]);
            if (1 !== $affected->getDeletedCount()) {
                throw new \LogicException(sprintf('Expected record was removed but it is not. id: "%s"', $mongodbMessage['_id']->__toString()));
            }

            return $convertedMessage;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $dbalMessage
     *
     * @return MongodbMessage
     */
    protected function convertMessage(array $mongodbMessage)
    {
        $message = $this->context->createMessage();
        $message->setId($mongodbMessage['_id']->__toString());
        $message->setBody($mongodbMessage['body']);
        $message->setPriority((int) $mongodbMessage['priority']);
        $message->setRedelivered((bool) $mongodbMessage['redelivered']);
        $message->setPublishedAt((int) $mongodbMessage['published_at']);

        if ($mongodbMessage['headers']) {
            $message->setHeaders(JSON::decode($mongodbMessage['headers']));
        }

        if ($mongodbMessage['properties']) {
            $message->setProperties(JSON::decode($mongodbMessage['properties']));
        }

        return $message;
    }
}
