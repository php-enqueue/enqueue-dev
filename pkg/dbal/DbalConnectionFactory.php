<?php

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Interop\Queue\PsrConnectionFactory;

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
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to mysql localhost with default credentials.
     *
     * $config = [
     *   'connection' => []             - dbal connection options. see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
     *   'table_name' => 'enqueue',     - database table name.
     *   'polling_interval' => '1000',  - How often query for new messages (milliseconds)
     *   'lazy' => true,                - Use lazy database connection (boolean)
     * ]
     *
     * or
     *
     * mysql://user:pass@localhost:3606/db?charset=UTF-8
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'mysql:')
    {
        if (empty($config)) {
            $config = $this->parseDsn('mysql:');
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace_recursive([
            'connection' => [],
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
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

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if (false === parse_url($dsn)) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        if (!preg_match('/^([0-9a-z_]+):(.+)?$/', $dsn, $matches)) {
            throw new \LogicException('Schema is empty');
        }
        $schema = $matches[1];

        $supported = [
            'db2' => true,
            'ibm_db2' => true,
            'mssql' => true,
            'pdo_sqlsrv' => true,
            'mysql' => true,
            'mysql2' => true,
            'pdo_mysql' => true,
            'pgsql' => true,
            'postgres' => true,
            'postgresql' => true,
            'pdo_pgsql' => true,
            'sqlite' => true,
            'sqlite3' => true,
            'pdo_sqlite' => true,
        ];

        if (false == isset($supported[$schema])) {
            throw new \LogicException(sprintf(
                'The given DSN schema "%s" is not supported. There are supported schemes: "%s".',
                $schema,
                implode('", "', array_keys($supported))
            ));
        }

        return [
            'lazy' => true,
            'connection' => [
                'url' => $schema.':' === $dsn ? $schema.'://root@localhost' : $dsn,
            ],
        ];
    }
}
