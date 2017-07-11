<?php
namespace Enqueue\RdKafka;

use Interop\Queue\PsrConnectionFactory;

class RdKafkaConnectionFactory implements PsrConnectionFactory
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
     * ]
     *
     * or
     *
     * rdkafka://host:port
     *
     * @param array|string $config
     */
    public function __construct($config = 'rdkafka://')
    {
        if (empty($config) || 'rdkafka://' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }


    /**
     * {@inheritdoc}
     *
     * @return RdKafkaContext
     */
    public function createContext()
    {
        return new RdKafkaContext($this->config);
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {

    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'global' => [
                'metadata.broker.list' => 'localhost:9092',
            ],
        ];
    }
}
