<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

trait DbalConsumerHelperTrait
{
    abstract public function getContext(): DbalContext;

    abstract public function getConnection(): Connection;

    protected function fetchMessage(array $queues): ?array
    {
        $now = time();

        $result = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->getContext()->getTableName())
            ->andWhere('delivery_id IS NULL')
            ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
            ->andWhere('queue IN (:queues)')
            ->addOrderBy('priority', 'desc')
            ->addOrderBy('published_at', 'asc')
            ->setParameter('delayedUntil', $now, \Doctrine\DBAL\ParameterType::INTEGER)
            ->setParameter('queues', array_values($queues), \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->setMaxResults(1)
            ->execute()
            ->fetch()
        ;

        return $result ?: null;
    }

    protected function markMessageAsDeliveredToConsumer(array $message, string $deliveryId): void
    {
        $now = time();
        $redeliveryDelay = $this->getRedeliveryDelay() / 1000; // milliseconds to seconds

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
    }

    protected function getMessageByDeliveryId(string $deliveryId): array
    {
        return $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->getContext()->getTableName())
            ->andWhere('delivery_id = :deliveryId')
            ->setParameter('deliveryId', $deliveryId, Type::STRING)
            ->setMaxResults(1)
            ->execute()
            ->fetch()
        ;
    }
}
