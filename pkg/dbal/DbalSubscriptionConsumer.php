<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Types\Type;
use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;

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
     * @param DbalContext $context
     */
    public function __construct(DbalContext $context)
    {
        $this->context = $context;
        $this->dbal = $this->context->getDbalConnection();
        $this->subscribers = [];
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('No subscribers');
        }

        $timeout = (int) ceil($timeout / 1000);
        $endAt = time() + $timeout;

        $queueNames = [];
        foreach (array_keys($this->subscribers) as $queueName) {
            $queueNames[$queueName] = $queueName;
        }

        $currentQueueNames = [];
        while (true) {
            if (empty($currentQueueNames)) {
                $currentQueueNames = $queueNames;
            }

            $message = $this->fetchPrioritizedMessage($currentQueueNames) ?: $this->fetchMessage($currentQueueNames);

            if ($message) {
                $this->dbal->delete($this->context->getTableName(), ['id' => $message['id']], ['id' => Type::GUID]);

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

            if ($timeout && microtime(true) >= $endAt) {
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
        $query = $this->dbal->createQueryBuilder();
        $query
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('queue IN (:queues)')
            ->andWhere('priority IS NULL')
            ->andWhere('(delayed_until IS NULL OR delayed_until <= :delayedUntil)')
            ->addOrderBy('published_at', 'asc')
            ->setMaxResults(1)
        ;

        $sql = $query->getSQL().' '.$this->dbal->getDatabasePlatform()->getWriteLockSQL();

        $result = $this->dbal->executeQuery(
            $sql,
            [
                'queues' => array_keys($queues),
                'delayedUntil' => time(),
            ],
            [
                'queues' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
                'delayedUntil' => \Doctrine\DBAL\ParameterType::INTEGER,
            ]
        )->fetch();

        return $result ?: null;
    }

    private function fetchPrioritizedMessage(array $queues): ?array
    {
        $query = $this->dbal->createQueryBuilder();
        $query
            ->select('*')
            ->from($this->context->getTableName())
            ->andWhere('queue IN (:queues)')
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
                'queues' => array_keys($queues),
                'delayedUntil' => time(),
            ],
            [
                'queues' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
                'delayedUntil' => \Doctrine\DBAL\ParameterType::INTEGER,
            ]
        )->fetch();

        return $result ?: null;
    }
}
