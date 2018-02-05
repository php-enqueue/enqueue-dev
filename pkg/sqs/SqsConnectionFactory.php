<?php

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Interop\Queue\PsrConnectionFactory;

class SqsConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var SqsClient
     */
    private $client;

    /**
     * $config = [
     *   'key' => null              - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,          - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,           - AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,          - (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'retries' => 3,            - (int, default=int(3)) Configures the maximum number of allowed retries for a client (pass 0 to disable retries).
     *   'version' => '2012-11-05', - (string, required) The version of the webservice to utilize
     *   'lazy' => true,            - Enable lazy connection (boolean)
     *   'endpoint' => null         - (string, default=null) The full URI of the webservice. This is only required when connecting to a custom endpoint e.g. localstack
     * ].
     *
     * or
     *
     * sqs:
     * sqs::?key=aKey&secret=aSecret&token=aToken
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'sqs:')
    {
        if (empty($config) || 'sqs:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $dsn = array_key_exists('dsn', $config) ? $config['dsn'] : null;
            unset($config['dsn']);

            if ($dsn) {
                $config = array_replace($config, $this->parseDsn($dsn));
            }
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new SqsContext(function () {
                return $this->establishConnection();
            });
        }

        return new SqsContext($this->establishConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * @return SqsClient
     */
    private function establishConnection()
    {
        if ($this->client) {
            return $this->client;
        }

        $config = [
            'version' => $this->config['version'],
            'retries' => $this->config['retries'],
            'region' => $this->config['region'],
        ];

        if (isset($this->config['endpoint'])) {
            $config['endpoint'] = $this->config['endpoint'];
        }

        if ($this->config['key'] && $this->config['secret']) {
            $config['credentials'] = [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ];

            if ($this->config['token']) {
                $config['credentials']['token'] = $this->config['token'];
            }
        }

        $this->client = new SqsClient($config);

        return $this->client;
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if (false === strpos($dsn, 'sqs:')) {
            throw new \LogicException(sprintf('The given DSN "%s" is not supported. Must start with "sqs:".', $dsn));
        }

        if (false === $config = parse_url($dsn)) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        if ($query = parse_url($dsn, PHP_URL_QUERY)) {
            $queryConfig = [];
            parse_str($query, $queryConfig);

            $config = array_replace($queryConfig, $config);
        }

        unset($config['query'], $config['scheme']);

        $config['lazy'] = empty($config['lazy']) ? false : true;

        return $config;
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
            'lazy' => true,
            'endpoint' => null
        ];
    }
}
