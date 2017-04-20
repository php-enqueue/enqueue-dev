<?php
namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;

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
            'tableName' => 'enqueue',
            'pollingInterval' => null,
        ], $config);

        if ($connection instanceof Connection) {
            $this->connection = $connection;
        } elseif (is_callable($connection)) {
            $this->connectionFactory = $connection;
        } else {
            throw new \InvalidArgumentException('The connection argument must be either Doctrine\DBAL\Connection or callable that returns Doctrine\DBAL\Connection.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
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

        if (isset($this->config['pollingInterval'])) {
            $consumer->setPollingInterval($this->config['pollingInterval']);
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
        return $this->config['tableName'];
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
}
