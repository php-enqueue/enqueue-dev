<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Aws\Sdk;
use Aws\Sns\SnsClient as AwsSnsClient;
use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class SnsConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var SnsClient
     */
    private $client;

    /**
     * $config = [
     *   'key' => null,               AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,            AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,             AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,            (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'version' => '2012-11-05',   (string, required) The version of the webservice to utilize
     *   'lazy' => true,              Enable lazy connection (boolean)
     *   'endpoint' => null,          (string, default=null) The full URI of the webservice. This is only required when connecting to a custom endpoint e.g. localstack
     *   'topic_arns' => [],          (array<string,string>) The list of existing topic arns: key - topic name; value - arn
     * ].
     *
     * or
     *
     * sns:
     * sns::?key=aKey&secret=aSecret&token=aToken
     *
     * @param array|string|SnsClient|null $config
     */
    public function __construct($config = 'sns:')
    {
        if ($config instanceof AwsSnsClient) {
            $this->client = new SnsClient($config);
            $this->config = ['lazy' => false] + $this->defaultConfig();

            return;
        }

        if (empty($config)) {
            $config = [];
        } elseif (\is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (\is_array($config)) {
            if (\array_key_exists('dsn', $config)) {
                $config = \array_replace_recursive($config, $this->parseDsn($config['dsn']));

                unset($config['dsn']);
            }
        } else {
            throw new \LogicException(\sprintf('The config must be either an array of options, a DSN string, null or instance of %s', AwsSnsClient::class));
        }

        $this->config = \array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return SnsContext
     */
    public function createContext(): Context
    {
        return new SnsContext($this->establishConnection(), $this->config);
    }

    private function establishConnection(): SnsClient
    {
        if ($this->client) {
            return $this->client;
        }

        $config = [
            'version' => $this->config['version'],
            'region' => $this->config['region'],
        ];

        if (isset($this->config['endpoint'])) {
            $config['endpoint'] = $this->config['endpoint'];
        }

        if (isset($this->config['profile'])) {
            $config['profile'] = $this->config['profile'];
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

        if (isset($this->config['http'])) {
            $config['http'] = $this->config['http'];
        }

        $establishConnection = function () use ($config) {
            return (new Sdk(['Sns' => $config]))->createMultiRegionSns();
        };

        $this->client = $this->config['lazy'] ?
            new SnsClient($establishConnection) :
            new SnsClient($establishConnection())
        ;

        return $this->client;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('sns' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(\sprintf('The given scheme protocol "%s" is not supported. It must be "sns"', $dsn->getSchemeProtocol()));
        }

        return \array_filter(\array_replace($dsn->getQuery(), [
            'key' => $dsn->getString('key'),
            'secret' => $dsn->getString('secret'),
            'token' => $dsn->getString('token'),
            'region' => $dsn->getString('region'),
            'version' => $dsn->getString('version'),
            'lazy' => $dsn->getBool('lazy'),
            'endpoint' => $dsn->getString('endpoint'),
            'topic_arns' => $dsn->getArray('topic_arns', [])->toArray(),
            'http' => $dsn->getArray('http', [])->toArray(),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'version' => '2010-03-31',
            'lazy' => true,
            'endpoint' => null,
            'topic_arns' => [],
            'http' => [],
        ];
    }
}
