<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use MongoDB\Client;

class MongodbConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to Mongodb localhost with default credentials.
     *
     * $config = [
     *   'dsn' => 'mongodb://127.0.0.1/' - Mongodb connection string. see http://docs.mongodb.org/manual/reference/connection-string/
     *   'dbname' => 'enqueue',          - database name.
     *   'collection_name' => 'enqueue'  - collection name
     *   'polling_interval' => '1000',   - How often query for new messages (milliseconds)
     * ]
     *
     * or
     *
     * mongodb://127.0.0.1:27017/dbname?polling_interval=1000&enqueue_collection=enqueue
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'mongodb:')
    {
        if (empty($config)) {
            $config = $this->parseDsn('mongodb:');
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $config = $this->parseDsn(empty($config['dsn']) ? 'mongodb:' : $config['dsn']);
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }
        $config = array_replace([
            'dsn' => 'mongodb://127.0.0.1/',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
        ], $config);

        $this->config = $config;
    }

    /**
     * @return MongodbContext
     */
    public function createContext(): Context
    {
        $client = new Client($this->config['dsn']);

        return new MongodbContext($client, $this->config);
    }

    public static function parseDsn(string $dsn): array
    {
        $parsedUrl = parse_url($dsn);
        if (false === $parsedUrl) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }
        if (empty($parsedUrl['scheme'])) {
            throw new \LogicException('Schema is empty');
        }
        $supported = [
            'mongodb' => true,
        ];
        if (false == isset($parsedUrl['scheme'])) {
            throw new \LogicException(sprintf(
                'The given DSN schema "%s" is not supported. There are supported schemes: "%s".',
                $parsedUrl['scheme'],
                implode('", "', array_keys($supported))
            ));
        }
        if ('mongodb:' === $dsn) {
            return [
                'dsn' => 'mongodb://127.0.0.1/',
            ];
        }
        $config['dsn'] = $dsn;
        if (isset($parsedUrl['path']) && '/' !== $parsedUrl['path']) {
            $pathParts = explode('/', $parsedUrl['path']);
            //DB name
            if ($pathParts[1]) {
                $config['dbname'] = $pathParts[1];
            }
        }
        if (isset($parsedUrl['query'])) {
            $queryParts = null;
            parse_str($parsedUrl['query'], $queryParts);
            //get enqueue attributes values
            if (!empty($queryParts['polling_interval'])) {
                $config['polling_interval'] = (int) $queryParts['polling_interval'];
            }
            if (!empty($queryParts['enqueue_collection'])) {
                $config['collection_name'] = $queryParts['enqueue_collection'];
            }
        }

        return $config;
    }
}
