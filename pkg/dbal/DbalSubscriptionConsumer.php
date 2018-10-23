<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Types\Type;
use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;
use Ramsey\Uuid\Uuid;

class DbalSubscriptionConsumer implements SubscriptionConsumer
{
    /**
     * @var DbalContext
     */
    private $context;

    /**
     * an item contains an array: [DbalConsumer $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbal;

    /**
     * Default 20 minutes in milliseconds.
     *
     * @var int
     */
    private $redeliveryDelay;

    /**
     * @param DbalContext $context
     */
    public function __construct(DbalContext $context)
    {
        $this->context = $context;
        $this->dbal = $this->context->getDbalConnection();
        $this->subscribers = [];

        $this->redeliveryDelay = 1200000;
    }

    /**
     * Get interval between retry failed messages in milliseconds.
     */
    public function getRedeliveryDelay(): int
    {
        return $this->redeliveryDelay;
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('No subscribers');
        }

        $now = time();
        $timeout /= 1000;
        $redeliveryDelay = $this->getRedeliveryDelay() / 1000; // milliseconds to seconds
        $deliveryId = (string) Uuid::uuid1();

        $queueNames = [];
        foreach (array_keys($this->subscribers) as $queueName) {
            $queueNames[$queueName] = $queueName;
        }

        $currentQueueNames = [];
        while (true) {
            if (empty($currentQueueNames)) {
                $currentQueueNames = $queueNames;
            }

            $message = $this->fetchMessage($currentQueueNames);

            if ($message) {
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

                $message = $this->dbal->createQueryBuilder()
                    ->select('*')
                    ->from($this->context->getTableName())
                    ->andWhere('delivery_id = :deliveryId')
                    ->setParameter('deliveryId', $deliveryId, Type::STRING)
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch()
                ;

                $dbalMessage = $this->context->convertMessage($message);

                /**
                 * @var DbalConsumer
                 * @var callable     $callback
                 */
                list($consumer, $callback) = $this->subscribers[$message['queue']];

                if (false === call_user_func($callback, $dbalMessage, $consumer)) {
                    return;
                }

                unset($currentQueueNames[$message['queue']]);
            } else {
                $currentQueueNames = [];

                usleep(200000); // 200ms
            }

            if ($timeout && microtime(true) >= $now + $timeout) {
                return;
            }
        }
    }

    /**
     * @param DbalConsumer $consumer
     */
    public function subscribe(Consumer $consumer, callable $callback): void
    {
        if (false == $consumer instanceof DbalConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', DbalConsumer::class, get_class($consumer)));
        }

        $queueName = $consumer->getQueue()->getQueueName();
        if (array_key_exists($queueName, $this->subscribers)) {
            if ($this->subscribers[$queueName][0] === $consumer && $this->subscribers[$queueName][1] === $callback) {
                return;
            }

            throw new \InvalidArgumentException(sprintf('There is a consumer subscribed to queue: "%s"', $queueName));
        }

        $this->subscribers[$queueName] = [$consumer, $callback];
    }

    /**
     * @param DbalConsumer $consumer
     */
    public function unsubscribe(Consumer $consumer): void
    {
        if (false == $consumer instanceof DbalConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', DbalConsumer::class, get_class($consumer)));
        }

        $queueName = $consumer->getQueue()->getQueueName();

        if (false == array_key_exists($queueName, $this->subscribers)) {
            return;
        }

        if ($this->subscribers[$queueName][0] !== $consumer) {
            return;
        }

        unset($this->subscribers[$queueName]);
    }

    public function unsubscribeAll(): void
    {
        $this->subscribers = [];
    }

    private function fetchMessage(array $queues): ?array
    {
        $result = $this->dbal->createQueryBuilder()
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('delivery_id IS NULL')
            ->andWhere('queue IN (:queues)')
            ->andWhere('delayed_until IS NULL OR delayed_until <= :delayedUntil')
            ->addOrderBy('priority', 'desc')
            ->addOrderBy('published_at', 'asc')
            ->setParameter('queues', array_keys($queues), \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->setParameter('delayedUntil', time(), \Doctrine\DBAL\ParameterType::INTEGER)
            ->setMaxResults(1)
            ->execute()
            ->fetch()
        ;

        return $result ?: null;
    }
}
