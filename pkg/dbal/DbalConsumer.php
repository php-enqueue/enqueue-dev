<?php

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class DbalConsumer implements PsrConsumer
{
    /**
     * @var DbalContext
     */
    private $context;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var DbalDestination
     */
    private $queue;

    /**
     * @var int microseconds
     */
    private $pollingInterval = 1000000;

    /**
     * @param DbalContext     $context
     * @param DbalDestination $queue
     */
    public function __construct(DbalContext $context, DbalDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->dbal = $this->context->getDbalConnection();
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
     * @return DbalDestination
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage|null
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
     * @return DbalMessage|null
     */
    public function receiveNoWait()
    {
        return $this->receiveMessage();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        // does nothing
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }
    }

    /**
     * @return DbalMessage|null
     */
    protected function receiveMessage()
    {
        $this->dbal->beginTransaction();
        try {
            $now = time();

            $dbalMessage = $this->fetchPrioritizedMessage($now) ?: $dbalMessage = $this->fetchMessage($now);
            if (false == $dbalMessage) {
                $this->dbal->commit();

                return;
            }

            // remove message
            $affectedRows = $this->dbal->delete($this->context->getTableName(), ['id' => $dbalMessage['id']], [
                'id' => Type::GUID,
            ]);

            if (1 !== $affectedRows) {
                throw new \LogicException(sprintf('Expected record was removed but it is not. id: "%s"', $dbalMessage['id']));
            }

            $this->dbal->commit();

            if (empty($dbalMessage['time_to_live']) || $dbalMessage['time_to_live'] > time()) {
                return $this->convertMessage($dbalMessage);
            }
        } catch (\Exception $e) {
            $this->dbal->rollBack();

            throw $e;
        }
    }

    /**
     * @param array $dbalMessage
     *
     * @return DbalMessage
     */
    protected function convertMessage(array $dbalMessage)
    {
        $message = $this->context->createMessage();

        $message->setBody($dbalMessage['body']);
        $message->setPriority((int) $dbalMessage['priority']);
        $message->setRedelivered((bool) $dbalMessage['redelivered']);
        $message->setPublishedAt((int) $dbalMessage['published_at']);

        if ($dbalMessage['headers']) {
            $message->setHeaders(JSON::decode($dbalMessage['headers']));
        }

        if ($dbalMessage['properties']) {
            $message->setProperties(JSON::decode($dbalMessage['properties']));
        }

        return $message;
    }

    /**
     * @param int $now
     *
     * @return array|null
     */
    private function fetchPrioritizedMessage($now)
    {
        $query = $this->dbal->createQueryBuilder();
        $query
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('queue = :queue')
            ->andWhere('priority IS NOT NULL')
            ->andWhere('(delayed_until IS NULL OR delayed_until <= :delayedUntil)')
            ->addOrderBy('priority', 'desc')
            ->addOrderBy('published_at', 'asc')
            ->setMaxResults(1)
        ;

        $sql = $query->getSQL().' '.$this->dbal->getDatabasePlatform()->getWriteLockSQL();

        return $this->dbal->executeQuery(
            $sql,
            [
                'queue' => $this->queue->getQueueName(),
                'delayedUntil' => $now,
            ],
            [
                'queue' => Type::STRING,
                'delayedUntil' => Type::INTEGER,
            ]
        )->fetch();
    }

    /**
     * @param int $now
     *
     * @return array|null
     */
    private function fetchMessage($now)
    {
        $query = $this->dbal->createQueryBuilder();
        $query
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('queue = :queue')
            ->andWhere('priority IS NULL')
            ->andWhere('(delayed_until IS NULL OR delayed_until <= :delayedUntil)')
            ->addOrderBy('published_at', 'asc')
            ->setMaxResults(1)
        ;

        $sql = $query->getSQL().' '.$this->dbal->getDatabasePlatform()->getWriteLockSQL();

        return $this->dbal->executeQuery(
            $sql,
            [
                'queue' => $this->queue->getQueueName(),
                'delayedUntil' => $now,
            ],
            [
                'queue' => Type::STRING,
                'delayedUntil' => Type::INTEGER,
            ]
        )->fetch();
    }
}
