<?php

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;

class DbalContext implements PsrContext
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
     *
     * @return DbalMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        $message = new DbalMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function createQueue($name)
    {
        return new DbalDestination($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function createTopic($name)
    {
        return new DbalDestination($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \BadMethodCallException('Dbal transport does not support temporary queues');
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalProducer
     */
    public function createProducer()
    {
        return new DbalProducer($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);

        $consumer = new DbalConsumer($this, $destination);

        if (isset($this->config['polling_interval'])) {
            $consumer->setPollingInterval($this->config['polling_interval']);
        }

        return $consumer;
    }

    public function close()
    {
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->config['table_name'];
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Connection
     */
    public function getDbalConnection()
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

    public function createDataBaseTable()
    {
        $sm = $this->getDbalConnection()->getSchemaManager();

        if ($sm->tablesExist([$this->getTableName()])) {
            return;
        }

        $table = new Table($this->getTableName());

        if ($this->getDbalConnection()->getDatabasePlatform()->hasNativeGuidType()) {
            $table->addColumn('id', 'guid');
        } else {
            $table->addColumn('id', 'binary', ['length' => 16]);
        }

        $table->addColumn('human_id', 'string', ['length' => 36]);
        $table->addColumn('published_at', 'bigint');
        $table->addColumn('body', 'text', ['notnull' => false]);
        $table->addColumn('headers', 'text', ['notnull' => false]);
        $table->addColumn('properties', 'text', ['notnull' => false]);
        $table->addColumn('redelivered', 'boolean', ['notnull' => false]);
        $table->addColumn('queue', 'string');
        $table->addColumn('priority', 'smallint');
        $table->addColumn('delayed_until', 'integer', ['notnull' => false]);
        $table->addColumn('time_to_live', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['published_at']);
        $table->addIndex(['queue']);
        $table->addIndex(['priority']);
        $table->addIndex(['delayed_until']);

        $sm->createTable($table);
    }
}
