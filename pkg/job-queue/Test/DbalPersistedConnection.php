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

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return false;
        }

        if ($this->hasPersistedConnection()) {
            $this->_conn = $this->getPersistedConnection();
            $this->setConnected(true);
        } else {
            parent::connect();
            $this->persistConnection($this->_conn);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->wrapTransactionNestingLevel('beginTransaction');
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->wrapTransactionNestingLevel('commit');
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        $this->wrapTransactionNestingLevel('rollBack');
    }

    /**
     * @param bool $connected
     */
    protected function setConnected($connected)
    {
        $rc = new \ReflectionClass(Connection::class);
        $rp = $rc->hasProperty('isConnected') ?
            $rc->getProperty('isConnected') :
            $rc->getProperty('_isConnected')
        ;

        $rp->setAccessible(true);
        $rp->setValue($this, $connected);
        $rp->setAccessible(false);
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

    /**
     * @param DriverConnection $connection
     */
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
    private function wrapTransactionNestingLevel($method)
    {
        $e = null;

        $this->setTransactionNestingLevel($this->getPersistedTransactionNestingLevel());

        try {
            call_user_func(['parent', $method]);
        } catch (\Exception $e) {
        }

        $this->persistTransactionNestingLevel($this->getTransactionNestingLevel());

        if ($e) {
            throw $e;
        }
    }
}
