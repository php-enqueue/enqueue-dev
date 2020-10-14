<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class RdKafkaConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default settings.
     *
     * [
     *     'global' => [                                   // https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
     *       'metadata.broker.list' => 'localhost:9092',
     *     ],
     *     'topic' => [],
     *     'dr_msg_cb' => null,
     *     'error_cb' => null,
     *     'rebalance_cb' => null,
     *     'partitioner' => null,                          // https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka-topicconf.setpartitioner.html
     *     'log_level' => null,
     *     'commit_async' => false,
     *     'shutdown_timeout' => -1,                       // https://github.com/arnaud-lb/php-rdkafka#proper-shutdown
     * ]
     *
     * or
     *
     * kafka://host:port
     *
     * @param array|string $config
     */
    public function __construct($config = 'kafka:')
    {
        if (version_compare(RdKafkaContext::getLibrdKafkaVersion(), '1.0.0', '<')) {
            throw new \RuntimeException('You must install librdkafka:1.0.0 or higher');
        }

        if (empty($config) || 'kafka:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace_recursive($this->defaultConfig(), $config);
    }

    /**
     * @return RdKafkaContext
     */
    public function createContext(): Context
    {
        return new RdKafkaContext($this->config);
    }

    private function parseDsn(string $dsn): array
    {
        $dsnConfig = parse_url($dsn);
        if (false === $dsnConfig) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ], $dsnConfig);

        if ('kafka' !== $dsnConfig['scheme']) {
            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "kafka" only.', $dsnConfig['scheme']));
        }

        $config = [];
        if ($dsnConfig['query']) {
            parse_str($dsnConfig['query'], $config);
        }

        $broker = $dsnConfig['host'];
        if ($dsnConfig['port']) {
            $broker .= ':'.$dsnConfig['port'];
        }

        $config['global']['metadata.broker.list'] = $broker;

        return array_replace_recursive($this->defaultConfig(), $config);
    }

    private function defaultConfig(): array
    {
        return [
            'global' => [
                'group.id' => uniqid('', true),
                'metadata.broker.list' => 'localhost:9092',
            ],
        ];
    }
}
