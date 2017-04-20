<?php
namespace Enqueue\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Enqueue\Psr\PsrConnectionFactory;

class DbalConnectionFactory implements PsrConnectionFactory
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
     *   'connectionName' => Dbal connection name.
     *   'tableName'  => Database table name.
     *   'pollingInterval' => msec How often query for new messages
     *   'lazy' => bool Use lazy database connection
     * ].
     *
     * @param $config
     */
    public function __construct(ManagerRegistry $registry, array $config = [])
    {
        $this->config = array_replace([
            'connectionName' => null,
            'lazy' => true,
        ], $config);

        $this->registry = $registry;
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
     * @return Connection
     */
    private function establishConnection()
    {
        return $this->registry->getConnection($this->config['connectionName']);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }
}
