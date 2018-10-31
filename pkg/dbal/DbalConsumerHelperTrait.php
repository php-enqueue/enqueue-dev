<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;

trait DbalConsumerHelperTrait
{
    abstract protected function getContext(): DbalContext;

    abstract protected function getConnection(): Connection;

    protected function fetchMessage(array $queues, int $redeliveryDelay): ?array
    {
        $now = time();
        $deliveryId = (string) Uuid::uuid1();

        $this->getConnection()->beginTransaction();

        try {
            $query = $this->getConnection()->createQueryBuilder()
                ->select('*')
                ->from($this->getContext()->getTableName())
                ->andWhere('delivery_id IS NULL')
                ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
                ->andWhere('queue IN (:queues)')
                ->addOrderBy('priority', 'desc')
                ->addOrderBy('published_at', 'asc')
                ->setMaxResults(1);

            // select for update
            $message = $this->getConnection()->executeQuery(
                $query->getSQL().' '.$this->getConnection()->getDatabasePlatform()->getWriteLockSQL(),
                ['delayedUntil' => $now, 'queues' => array_values($queues)],
                ['delayedUntil' => ParameterType::INTEGER, 'queues' => Connection::PARAM_STR_ARRAY]
            )->fetch();

            if (!$message) {
                $this->getConnection()->commit();

                return null;
            }

            // mark message as delivered to consumer
            $this->getConnection()->createQueryBuilder()
                ->andWhere('id = :id')
                ->update($this->getContext()->getTableName())
                ->set('delivery_id', ':deliveryId')
                ->set('redeliver_after', ':redeliverAfter')
                ->setParameter('id', $message['id'], Type::GUID)
                ->setParameter('deliveryId', $deliveryId, Type::STRING)
                ->setParameter('redeliverAfter', $now + $redeliveryDelay, Type::BIGINT)
                ->execute()
            ;

            $this->getConnection()->commit();

            $deliveredMessage = $this->getConnection()->createQueryBuilder()
                ->select('*')
                ->from($this->getContext()->getTableName())
                ->andWhere('delivery_id = :deliveryId')
                ->setParameter('deliveryId', $deliveryId, Type::STRING)
                ->setMaxResults(1)
                ->execute()
                ->fetch()
            ;

            return $deliveredMessage ?: null;
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();

            throw $e;
        }
    }

    protected function redeliverMessages(): void
    {
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
    }

    protected function removeExpiredMessages(): void
    {
        $this->getConnection()->createQueryBuilder()
            ->delete($this->getContext()->getTableName())
            ->andWhere('(time_to_live IS NOT NULL) AND (time_to_live < :now)')
            ->setParameter(':now', (int) time(), Type::BIGINT)
            ->setParameter('redelivered', false, Type::BOOLEAN)
            ->execute()
        ;
    }
}
