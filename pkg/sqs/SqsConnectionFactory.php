<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Enqueue\Dsn\Dsn;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;

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
     * @param array|string|SqsClient|null $config
     */
    public function __construct($config = 'sqs:')
    {
        if ($config instanceof SqsClient) {
            $this->client = $config;
            $this->config = ['lazy' => false] + $this->defaultConfig();

            return;
        }

        if (empty($config)) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            if (array_key_exists('dsn', $config)) {
                $config = array_replace_recursive($config, $this->parseDsn($config['dsn']));

                unset($config['dsn']);
            }
        } else {
            throw new \LogicException(sprintf('The config must be either an array of options, a DSN string, null or instance of %s', SqsClient::class));
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return SqsContext
     */
    public function createContext(): PsrContext
    {
        if ($this->config['lazy']) {
            return new SqsContext(function () {
                return $this->establishConnection();
            });
        }

        return new SqsContext($this->establishConnection());
    }

    private function establishConnection(): SqsClient
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

    private function parseDsn(string $dsn): array
    {
        $dsn = new Dsn($dsn);

        if ('sqs' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "sqs"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'key' => $dsn->getQueryParameter('key'),
            'secret' => $dsn->getQueryParameter('secret'),
            'token' => $dsn->getQueryParameter('token'),
            'region' => $dsn->getQueryParameter('region'),
            'retries' => $dsn->getInt('retries'),
            'version' => $dsn->getQueryParameter('version'),
            'lazy' => $dsn->getBool('lazy'),
            'endpoint' => $dsn->getQueryParameter('endpoint'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'retries' => 3,
            'version' => '2012-11-05',
            'lazy' => true,
            'endpoint' => null,
        ];
    }
}
