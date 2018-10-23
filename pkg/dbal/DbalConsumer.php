<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use function GuzzleHttp\Psr7\str;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Impl\ConsumerPollingTrait;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Ramsey\Uuid\Uuid;

class DbalConsumer implements Consumer
{
    use ConsumerPollingTrait;

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
     * Default 20 minutes in milliseconds.
     *
     * @var int
     */
    private $redeliveryDelay;

    public function __construct(DbalContext $context, DbalDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->dbal = $this->context->getDbalConnection();

        $this->redeliveryDelay = 1200000;
    }

    /**
     * Get interval between retry failed messages in milliseconds.
     */
    public function getRedeliveryDelay(): int
    {
        return $this->redeliveryDelay;
    }

    /**
     * Interval between retry failed messages in seconds.
     */
    public function setRedeliveryDelay(int $redeliveryDelay): self
    {
        $this->redeliveryDelay = $redeliveryDelay;

        return $this;
    }

    /**
     * @return DbalDestination
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function receiveNoWait(): ?Message
    {
        $this->redeliverMessages();

        $this->dbal->beginTransaction();
        try {
            $now = (int) time();
            $redeliveryDelay = $this->getRedeliveryDelay() / 1000; // milliseconds to seconds
            $deliveryId = (string) Uuid::uuid1();

            // get top message from the queue
            $message = $this->fetchMessage($now);

            if (null == $message) {
                $this->dbal->commit();

                return null;
            }

            // mark message as delivered to consumer
            $this->dbal->createQueryBuilder()
                ->update($this->context->getTableName())
                ->set('delivery_id', ':deliveryId')
                ->set('redeliver_after', ':redeliverAfter')
                ->andWhere('id = :id')
                ->setParameter('id', $message['id'], Type::GUID)
                ->setParameter('deliveryId', $deliveryId, Type::STRING)
                ->setParameter('redeliverAfter', $now + $redeliveryDelay, Type::BIGINT)
                ->execute()
            ;

            $dbalMessage = $this->dbal->createQueryBuilder()
                ->select('*')
                ->from($this->context->getTableName())
                ->andWhere('delivery_id = :deliveryId')
                ->setParameter('deliveryId', $deliveryId, Type::STRING)
                ->setMaxResults(1)
                ->execute()
                ->fetch()
            ;

            $this->dbal->commit();

            if ($message->isRedelivered() || empty($dbalMessage['time_to_live']) || $dbalMessage['time_to_live'] > time()) {
                return $this->context->convertMessage($dbalMessage);
            }

            return null;
        } catch (\Exception $e) {
            $this->dbal->rollBack();

            throw $e;
        }
    }

    /**
     * @param DbalMessage $message
     */
    public function acknowledge(Message $message): void
    {
        $this->deleteMessage($message->getDeliveryId());
    }

    /**
     * @param DbalMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);

            return;
        }

        $this->deleteMessage($message->getDeliveryId());
    }

    private function deleteMessage(?string $deliveryId): void
    {
        $this->dbal->delete(
            $this->context->getTableName(),
            ['delivery_id' => $deliveryId],
            ['delivery_id' => Type::STRING]
        );
    }

    private function fetchMessage(int $now): ?array
    {
        $result = $this->dbal->createQueryBuilder()
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('delivery_id IS NULL')
            ->andWhere('queue = :queue')
            ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
            ->addOrderBy('priority', 'desc')
            ->addOrderBy('published_at', 'asc')
            ->setParameter('queue', $this->queue->getQueueName(), Type::STRING)
            ->setParameter('delayedUntil', $now, Type::BIGINT)
            ->setMaxResults(1)
            ->execute()
            ->fetch()
        ;

        return $result ?: null;
    }

    private function redeliverMessages(): void
    {
        $this->dbal->createQueryBuilder()
            ->update($this->context->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redelivered', ':redelivered')
            ->andWhere('delivery_id IS NOT NULL')
            ->andWhere('redeliver_after < :now')
            ->setParameter(':now', (int) time(), Type::BIGINT)
            ->setParameter('deliveryId', null, Type::STRING)
            ->setParameter('redelivered', true, Type::BOOLEAN)
            ->execute()
        ;
    }
}
