<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Enqueue\Dsn\Dsn;
use Interop\Queue\Context;

final class RdKafkaConnectionFactory implements RdKafkaConnectionFactoryInterface
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
        if (version_compare($this->getLibrdKafkaVersion(), '1.0.0', '<')) {
            throw new \RuntimeException('You must install librdkafka:1.0.0 or higher');
        }

        if (empty($config) || 'kafka:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (!is_array($config)) {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace_recursive($this->defaultConfig(), $config);
    }

    /**
     * @return RdKafkaContextInterface
     */
    public function createContext(): Context
    {
        return new RdKafkaContext($this->config);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('kafka' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "kafka" only.', $dsn->getSchemeProtocol()));
        }

        $config = $dsn->getQuery();

        $broker = $dsn->getHost();

        if (null !== $dsn->getPort()) {
            $broker .= ':'.$dsn->getPort();
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

    private function getLibrdKafkaVersion(): string
    {
        if (!defined('RD_KAFKA_VERSION')) {
            throw new \RuntimeException('RD_KAFKA_VERSION constant is not defined. Phprdkafka is probably not installed');
        }
        $major = (RD_KAFKA_VERSION & 0xFF000000) >> 24;
        $minor = (RD_KAFKA_VERSION & 0x00FF0000) >> 16;
        $patch = (RD_KAFKA_VERSION & 0x0000FF00) >> 8;

        return "$major.$minor.$patch";
    }
}
