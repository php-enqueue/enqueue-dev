<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;

trait DbalConsumerHelperTrait
{
    private $redeliverMessagesLastExecutedAt;

    private $removeExpiredMessagesLastExecutedAt;

    abstract protected function getContext(): DbalContext;

    abstract protected function getConnection(): Connection;

    protected function fetchMessage(array $queues, int $redeliveryDelay): ?DbalMessage
    {
        if (empty($queues)) {
            throw new \LogicException('Queues must not be empty.');
        }

        $now = time();
        $deliveryId = Uuid::uuid4();

        $endAt = microtime(true) + 0.2; // add 200ms

        $select = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from($this->getContext()->getTableName())
            ->andWhere('queue IN (:queues)')
            ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
            ->andWhere('delivery_id IS NULL')
            ->addOrderBy('priority', 'asc')
            ->addOrderBy('published_at', 'asc')
            ->setParameter('queues', $queues, Connection::PARAM_STR_ARRAY)
            ->setParameter('delayedUntil', $now, Type::INTEGER)
            ->setMaxResults(1);

        $update = $this->getConnection()->createQueryBuilder()
            ->update($this->getContext()->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redeliver_after', ':redeliverAfter')
            ->andWhere('id = :messageId')
            ->andWhere('delivery_id IS NULL')
            ->setParameter('deliveryId', $deliveryId, Type::GUID)
            ->setParameter('redeliverAfter', $now + $redeliveryDelay, Type::BIGINT)
        ;

        while (microtime(true) < $endAt) {
            try {
                $result = $select->execute()->fetch();
                if (empty($result)) {
                    return null;
                }

                $update
                    ->setParameter('messageId', $result['id'], Type::GUID);

                if ($update->execute()) {
                    $deliveredMessage = $this->getConnection()->createQueryBuilder()
                        ->select('*')
                        ->from($this->getContext()->getTableName())
                        ->andWhere('delivery_id = :deliveryId')
                        ->setParameter('deliveryId', $deliveryId, Type::GUID)
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();

                    // the message has been removed by a 3rd party, such as truncate operation.
                    if (false === $deliveredMessage) {
                        continue;
                    }

                    if ($deliveredMessage['redelivered'] || empty($deliveredMessage['time_to_live']) || $deliveredMessage['time_to_live'] > time()) {
                        return $this->getContext()->convertMessage($deliveredMessage);
                    }
                }
            } catch (RetryableException $e) {
                // maybe next time we'll get more luck
            }
        }

        return null;
    }

    protected function redeliverMessages(): void
    {
        if (null === $this->redeliverMessagesLastExecutedAt) {
            $this->redeliverMessagesLastExecutedAt = microtime(true);
        } elseif ((microtime(true) - $this->redeliverMessagesLastExecutedAt) < 1) {
            return;
        }

        $update = $this->getConnection()->createQueryBuilder()
            ->update($this->getContext()->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redelivered', ':redelivered')
            ->andWhere('redeliver_after < :now')
            ->andWhere('delivery_id IS NOT NULL')
            ->setParameter(':now', time(), Type::BIGINT)
            ->setParameter('deliveryId', null, Type::GUID)
            ->setParameter('redelivered', true, Type::BOOLEAN)
        ;

        try {
            $update->execute();

            $this->redeliverMessagesLastExecutedAt = microtime(true);
        } catch (RetryableException $e) {
            // maybe next time we'll get more luck
        }
    }

    protected function removeExpiredMessages(): void
    {
        if (null === $this->removeExpiredMessagesLastExecutedAt) {
            $this->removeExpiredMessagesLastExecutedAt = microtime(true);
        } elseif ((microtime(true) - $this->removeExpiredMessagesLastExecutedAt) < 1) {
            return;
        }

        $delete = $this->getConnection()->createQueryBuilder()
            ->delete($this->getContext()->getTableName())
            ->andWhere('(time_to_live IS NOT NULL) AND (time_to_live < :now)')
            ->andWhere('delivery_id IS NULL')
            ->andWhere('redelivered = :redelivered')

            ->setParameter(':now', time(), Type::BIGINT)
            ->setParameter('redelivered', false, Type::BOOLEAN)
        ;

        try {
            $delete->execute();
        } catch (RetryableException $e) {
            // maybe next time we'll get more luck
        }

        $this->removeExpiredMessagesLastExecutedAt = microtime(true);
    }

    private function deleteMessage(string $deliveryId): void
    {
        if (empty($deliveryId)) {
            throw new \LogicException(sprintf('Expected record was removed but it is not. Delivery id: "%s"', $deliveryId));
        }

        $this->getConnection()->delete(
            $this->getContext()->getTableName(),
            ['delivery_id' => $deliveryId],
            ['delivery_id' => Type::GUID]
        );
    }
}
