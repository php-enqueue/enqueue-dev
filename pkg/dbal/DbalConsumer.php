<?php

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;

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
     * @var int
     */
    private $pollingInterval;

    public function __construct(DbalContext $context, DbalDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->dbal = $this->context->getDbalConnection();

        $this->pollingInterval = 1000;
    }

    /**
     * Polling interval is in milliseconds.
     */
    public function setPollingInterval(int $interval): void
    {
        $this->pollingInterval = $interval;
    }

    /**
     * Get polling interval in milliseconds.
     */
    public function getPollingInterval(): int
    {
        return $this->pollingInterval;
    }

    /**
     * @return DbalDestination
     */
    public function getQueue(): PsrQueue
    {
        return $this->queue;
    }

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

    public function receiveNoWait(): ?PsrMessage
    {
        return $this->receiveMessage();
    }

    /**
     * @param DbalMessage $message
     */
    public function acknowledge(PsrMessage $message): void
    {
        // does nothing
    }

    /**
     * @param DbalMessage $message
     */
    public function reject(PsrMessage $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }
    }

    protected function receiveMessage(): ?DbalMessage
    {
        $this->dbal->beginTransaction();
        try {
            $now = time();

            $dbalMessage = $this->fetchPrioritizedMessage($now) ?: $dbalMessage = $this->fetchMessage($now);
            if (false == $dbalMessage) {
                $this->dbal->commit();

                return null;
            }

            // remove message
            $affectedRows = $this->dbal->delete($this->context->getTableName(), ['id' => $dbalMessage['id']], [
                'id' => Type::GUID,
            ]);

            if (1 !== $affectedRows) {
                throw new \LogicException(sprintf('Expected record was removed but it is not. id: "%s"', $dbalMessage['id']));
            }

            $this->dbal->commit();

            if (empty($dbalMessage['time_to_live']) || ($dbalMessage['time_to_live'] / 1000) > microtime(true)) {
                return $this->convertMessage($dbalMessage);
            }

            return null;
        } catch (\Exception $e) {
            $this->dbal->rollBack();

            throw $e;
        }
    }

    protected function convertMessage(array $dbalMessage): DbalMessage
    {
        /** @var DbalMessage $message */
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

    private function fetchPrioritizedMessage(int $now): ?array
    {
        $query = $this->dbal->createQueryBuilder();
        $query
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('queue = :queue')
            ->andWhere('priority IS NOT NULL')
            ->andWhere('(delayed_until IS NULL OR delayed_until <= :delayedUntil)')
            ->addOrderBy('published_at', 'asc')
            ->addOrderBy('priority', 'desc')
            ->setMaxResults(1)
        ;

        $sql = $query->getSQL().' '.$this->dbal->getDatabasePlatform()->getWriteLockSQL();

        $result = $this->dbal->executeQuery(
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

        return $result ?: null;
    }

    private function fetchMessage(int $now): ?array
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

        $result = $this->dbal->executeQuery(
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

        return $result ?: null;
    }
}
