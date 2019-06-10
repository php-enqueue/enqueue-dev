<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class DbalContext implements Context
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var callable
     */
    private $connectionFactory;

    /**
     * @var array
     */
    private $config;

    /**
     * Callable must return instance of Doctrine\DBAL\Connection once called.
     *
     * @param Connection|callable $connection
     * @param array               $config
     */
    public function __construct($connection, array $config = [])
    {
        $this->config = array_replace([
            'table_name' => 'enqueue',
            'polling_interval' => null,
        ], $config);

        if ($connection instanceof Connection) {
            $this->connection = $connection;
        } elseif (is_callable($connection)) {
            $this->connectionFactory = $connection;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The connection argument must be either %s or callable that returns %s.',
                Connection::class,
                Connection::class
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        $message = new DbalMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * @return DbalDestination
     */
    public function createQueue(string $name): Queue
    {
        return new DbalDestination($name);
    }

    /**
     * @return DbalDestination
     */
    public function createTopic(string $name): Topic
    {
        return new DbalDestination($name);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return DbalProducer
     */
    public function createProducer(): Producer
    {
        return new DbalProducer($this);
    }

    /**
     * @return DbalConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);

        $consumer = new DbalConsumer($this, $destination);

        if (isset($this->config['polling_interval'])) {
            $consumer->setPollingInterval((int) $this->config['polling_interval']);
        }

        if (isset($this->config['redelivery_delay'])) {
            $consumer->setRedeliveryDelay((int) $this->config['redelivery_delay']);
        }

        return $consumer;
    }

    public function close(): void
    {
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        $consumer = new DbalSubscriptionConsumer($this);

        if (isset($this->config['redelivery_delay'])) {
            $consumer->setRedeliveryDelay($this->config['redelivery_delay']);
        }

        return $consumer;
    }

    /**
     * @internal It must be used here and in the consumer only
     */
    public function convertMessage(array $arrayMessage): DbalMessage
    {
        /** @var DbalMessage $message */
        $message = $this->createMessage(
            $arrayMessage['body'],
            $arrayMessage['properties'] ? JSON::decode($arrayMessage['properties']) : [],
            $arrayMessage['headers'] ? JSON::decode($arrayMessage['headers']) : []
        );

        if (isset($arrayMessage['id'])) {
            $message->setMessageId($arrayMessage['id']);
        }
        if (isset($arrayMessage['queue'])) {
            $message->setQueue($arrayMessage['queue']);
        }
        if (isset($arrayMessage['redelivered'])) {
            $message->setRedelivered((bool) $arrayMessage['redelivered']);
        }
        if (isset($arrayMessage['priority'])) {
            $message->setPriority((int) (-1 * $arrayMessage['priority']));
        }
        if (isset($arrayMessage['published_at'])) {
            $message->setPublishedAt((int) $arrayMessage['published_at']);
        }
        if (isset($arrayMessage['delivery_id'])) {
            $message->setDeliveryId($arrayMessage['delivery_id']);
        }
        if (isset($arrayMessage['redeliver_after'])) {
            $message->setRedeliverAfter((int) $arrayMessage['redeliver_after']);
        }

        return $message;
    }

    /**
     * @param DbalDestination $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        $this->getDbalConnection()->delete(
            $this->getTableName(),
            ['queue' => $queue->getQueueName()],
            ['queue' => Type::STRING]
        );
    }

    public function getTableName(): string
    {
        return $this->config['table_name'];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getDbalConnection(): Connection
    {
        if (false == $this->connection) {
            $connection = call_user_func($this->connectionFactory);
            if (false == $connection instanceof Connection) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of Doctrine\DBAL\Connection. It returns %s',
                    is_object($connection) ? get_class($connection) : gettype($connection)
                ));
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }

    public function createDataBaseTable(): void
    {
        $sm = $this->getDbalConnection()->getSchemaManager();

        if ($sm->tablesExist([$this->getTableName()])) {
            return;
        }

        $table = new Table($this->getTableName());

        $table->addColumn('id', Type::GUID, ['length' => 16, 'fixed' => true]);
        $table->addColumn('published_at', Type::BIGINT);
        $table->addColumn('body', Type::TEXT, ['notnull' => false]);
        $table->addColumn('headers', Type::TEXT, ['notnull' => false]);
        $table->addColumn('properties', Type::TEXT, ['notnull' => false]);
        $table->addColumn('redelivered', Type::BOOLEAN, ['notnull' => false]);
        $table->addColumn('queue', Type::STRING);
        $table->addColumn('priority', Type::SMALLINT, ['notnull' => false]);
        $table->addColumn('delayed_until', Type::BIGINT, ['notnull' => false]);
        $table->addColumn('time_to_live', Type::BIGINT, ['notnull' => false]);
        $table->addColumn('delivery_id', Type::GUID, ['length' => 16, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('redeliver_after', Type::BIGINT, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['priority', 'published_at', 'queue', 'delivery_id', 'delayed_until', 'id']);

        $table->addIndex(['redeliver_after', 'delivery_id']);
        $table->addIndex(['time_to_live', 'delivery_id']);

        $sm->createTable($table);
    }
}
