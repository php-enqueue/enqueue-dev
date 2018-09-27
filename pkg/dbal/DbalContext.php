<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Interop\Queue\TemporaryQueueNotSupportedException;
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
            $consumer->setPollingInterval($this->config['polling_interval']);
        }

        return $consumer;
    }

    public function close(): void
    {
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
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

        $table->addColumn('id', Type::BINARY, ['length' => 16, 'fixed' => true]);
        $table->addColumn('human_id', Type::STRING, ['length' => 36]);
        $table->addColumn('published_at', Type::BIGINT);
        $table->addColumn('body', Type::TEXT, ['notnull' => false]);
        $table->addColumn('headers', Type::TEXT, ['notnull' => false]);
        $table->addColumn('properties', Type::TEXT, ['notnull' => false]);
        $table->addColumn('redelivered', Type::BOOLEAN, ['notnull' => false]);
        $table->addColumn('queue', Type::STRING);
        $table->addColumn('priority', Type::SMALLINT, ['notnull' => false]);
        $table->addColumn('delayed_until', Type::BIGINT, ['notnull' => false]);
        $table->addColumn('time_to_live', Type::BIGINT, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['published_at']);
        $table->addIndex(['queue']);
        $table->addIndex(['priority']);
        $table->addIndex(['delayed_until']);
        $table->addIndex(['priority', 'published_at']);

        $sm->createTable($table);
    }
}
