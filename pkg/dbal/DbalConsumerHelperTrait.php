<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;

trait DbalConsumerHelperTrait
{
    private $redeliverMessagesLastExecutedAt;

    private $removeExpiredMessagesLastExecutedAt;

    abstract protected function getContext(): DbalContext;

    abstract protected function getConnection(): Connection;

    protected function fetchMessage(array $queues, int $redeliveryDelay): ?array
    {
        $now = time();
        $deliveryId = (string) Uuid::uuid1();

        $endAt = microtime(true) + 0.2; // add 200ms

        $select = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from($this->getContext()->getTableName())
            ->andWhere('delivery_id IS NULL')
            ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
            ->andWhere('queue IN (:queues)')
            ->addOrderBy('priority', 'asc')
            ->addOrderBy('published_at', 'asc')
            ->setParameter('delayedUntil', $now, ParameterType::INTEGER)
            ->setParameter('queues', array_values($queues), Connection::PARAM_STR_ARRAY)
            ->setMaxResults(1);

        $update = $this->getConnection()->createQueryBuilder()
            ->update($this->getContext()->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redeliver_after', ':redeliverAfter')
            ->andWhere('id = :messageId')
            ->andWhere('delivery_id IS NULL')
            ->setParameter('deliveryId', $deliveryId, Type::STRING)
            ->setParameter('redeliverAfter', $now + $redeliveryDelay, Type::BIGINT)
        ;

        while (microtime(true) < $endAt) {
            $result = $select->execute()->fetch();
            if (empty($result)) {
                return null;
            }

            $update
                ->setParameter('messageId', $result['id'], Type::GUID)
            ;

            if ($update->execute()) {
                $deliveredMessage = $this->getConnection()->createQueryBuilder()
                    ->select('*')
                    ->from($this->getContext()->getTableName())
                    ->andWhere('delivery_id = :deliveryId')
                    ->setParameter('deliveryId', $deliveryId, Type::STRING)
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch()
                ;

                if (false == $deliveredMessage) {
                    throw new \LogicException('There must be a message at all times at this stage but there is no a message.');
                }

                return $deliveredMessage;
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

        $this->getConnection()->createQueryBuilder()
            ->update($this->getContext()->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redelivered', ':redelivered')
            ->andWhere('delivery_id IS NOT NULL')
            ->andWhere('redeliver_after < :now')
            ->setParameter(':now', (int) time(), Type::BIGINT)
            ->setParameter('deliveryId', null, Type::STRING)
            ->setParameter('redelivered', true, Type::BOOLEAN)
            ->execute()
        ;

        $this->redeliverMessagesLastExecutedAt = microtime(true);
    }

    protected function removeExpiredMessages(): void
    {
        if (null === $this->removeExpiredMessagesLastExecutedAt) {
            $this->removeExpiredMessagesLastExecutedAt = microtime(true);
        } elseif ((microtime(true) - $this->removeExpiredMessagesLastExecutedAt) < 1) {
            return;
        }

        $this->getConnection()->createQueryBuilder()
            ->delete($this->getContext()->getTableName())
            ->andWhere('(time_to_live IS NOT NULL) AND (time_to_live < :now)')
            ->andWhere('(redelivered = false OR delivery_id IS NULL)')
            ->setParameter(':now', (int) time(), Type::BIGINT)
            ->execute()
        ;

        $this->removeExpiredMessagesLastExecutedAt = microtime(true);
    }
}
