<?php

namespace Enqueue\JobQueue\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

/**
 * Connection wrapper sharing the same db handle across multiple requests.
 *
 * Allows multiple Connection instances to run in the same transaction
 */
class DbalPersistedConnection extends Connection
{
    /**
     * @var DriverConnection[]
     */
    protected static $persistedConnections;

    /**
     * @var int[]
     */
    protected static $persistedTransactionNestingLevels;

    public function connect()
    {
        if ($this->isConnected()) {
            return false;
        }

        if ($this->hasPersistedConnection()) {
            $this->_conn = $this->getPersistedConnection();
        } else {
            parent::connect();
            $this->persistConnection($this->_conn);
        }

        return true;
    }

    public function beginTransaction()
    {
        return $this->wrapTransactionNestingLevel('beginTransaction');
    }

    public function commit()
    {
        return $this->wrapTransactionNestingLevel('commit');
    }

    public function rollBack()
    {
        return $this->wrapTransactionNestingLevel('rollBack');
    }

    /**
     * @return int
     */
    protected function getPersistedTransactionNestingLevel()
    {
        if (isset(static::$persistedTransactionNestingLevels[$this->getConnectionId()])) {
            return static::$persistedTransactionNestingLevels[$this->getConnectionId()];
        }

        return 0;
    }

    /**
     * @param int $level
     */
    protected function persistTransactionNestingLevel($level)
    {
        static::$persistedTransactionNestingLevels[$this->getConnectionId()] = $level;
    }

    protected function persistConnection(DriverConnection $connection)
    {
        static::$persistedConnections[$this->getConnectionId()] = $connection;
    }

    /**
     * @return bool
     */
    protected function hasPersistedConnection()
    {
        return isset(static::$persistedConnections[$this->getConnectionId()]);
    }

    /**
     * @return DriverConnection
     */
    protected function getPersistedConnection()
    {
        return static::$persistedConnections[$this->getConnectionId()];
    }

    /**
     * @return string
     */
    protected function getConnectionId()
    {
        return md5(serialize($this->getParams()));
    }

    /**
     * @param int $level
     */
    private function setTransactionNestingLevel($level)
    {
        $rc = new \ReflectionClass(Connection::class);
        $rp = $rc->hasProperty('transactionNestingLevel') ?
            $rc->getProperty('transactionNestingLevel') :
            $rc->getProperty('_transactionNestingLevel')
        ;

        $rp->setAccessible(true);
        $rp->setValue($this, $level);
        $rp->setAccessible(false);
    }

    /**
     * @param string $method
     *
     * @throws \Exception
     */
    private function wrapTransactionNestingLevel($method): bool
    {
        $e = null;

        $this->setTransactionNestingLevel($this->getPersistedTransactionNestingLevel());

        try {
            $result = call_user_func([parent::class, $method]);
        } catch (\Exception $e) {
        }

        $this->persistTransactionNestingLevel($this->getTransactionNestingLevel());

        if ($e) {
            throw $e;
        }

        return $result;
    }
}
