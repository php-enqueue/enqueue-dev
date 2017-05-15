<?php

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Enqueue\Psr\PsrConnectionFactory;

class DbalConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * $config = [
     *   'connection' => []             - dbal connection options. see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
     *   'table_name' => 'enqueue',     - database table name.
     *   'polling_interval' => '1000',  - How often query for new messages (milliseconds)
     *   'lazy' => true,                - Use lazy database connection (boolean)
     * ].
     *
     * @param $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace([
            'connection' => [],
            'lazy' => true,
        ], $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new DbalContext(function () {
                return $this->establishConnection();
            }, $this->config);
        }

        return new DbalContext($this->establishConnection(), $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * @return Connection
     */
    private function establishConnection()
    {
        if (false == $this->connection) {
            $this->connection = DriverManager::getConnection($this->config['connection']);
            $this->connection->connect();
        }

        return $this->connection;
    }
}
