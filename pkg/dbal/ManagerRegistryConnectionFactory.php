<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class ManagerRegistryConnectionFactory implements ConnectionFactory
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var array
     */
    private $config;

    /**
     * $config = [
     *   'connection_name' => null,     - doctrine dbal connection name
     *   'table_name' => 'enqueue',     - database table name.
     *   'polling_interval' => 1000,    - How often query for new messages (milliseconds)
     *   'lazy' => true,                - Use lazy database connection (boolean)
     * ].
     *
     * @param ManagerRegistry $registry
     * @param array           $config
     */
    public function __construct(ManagerRegistry $registry, array $config = [])
    {
        $this->config = array_replace([
            'connection_name' => null,
            'lazy' => true,
        ], $config);

        $this->registry = $registry;
    }

    /**
     * @return DbalContext
     */
    public function createContext(): Context
    {
        if ($this->config['lazy']) {
            return new DbalContext(function () {
                return $this->establishConnection();
            }, $this->config);
        }

        return new DbalContext($this->establishConnection(), $this->config);
    }

    public function close(): void
    {
    }

    private function establishConnection(): Connection
    {
        $connection = $this->registry->getConnection($this->config['connection_name']);
        $connection->connect();

        return $connection;
    }
}
