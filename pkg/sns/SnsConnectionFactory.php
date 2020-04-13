<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use AsyncAws\Sns\SnsClient as AsyncAwsSnsClient;
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
     * @var SnsClient|SnsAsyncClient
     */
    private $client;

    /**
     * $config = [
     *   'key' => null                AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'secret' => null,            AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'token' => null,             AWS credentials. If no credentials are provided, the SDK will attempt to load them from the environment.
     *   'region' => null,            (string, required) Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.
     *   'endpoint' => null           (string, default=null) The full URI of the webservice. This is only required when connecting to a custom endpoint e.g. localstack
     *   'profile' => null,           (string, default=null) The name of an AWS profile to used, if provided the SDK will attempt to read associated credentials from the ~/.aws/credentials file.
     * ].
     *
     * or
     *
     * sns:
     * sns::?key=aKey&secret=aSecret&token=aToken
     *
     * @param array|string|AwsSnsClient|AsyncAwsSnsClient|null $config
     */
    public function __construct($config = 'sns:')
    {
        if ($config instanceof AsyncAwsSnsClient) {
            $this->client = new SnsAsyncClient($config);
            $this->config = $this->defaultConfig();

            return;
        }
        if ($config instanceof AwsSnsClient) {
            $this->client = new SnsClient($config);
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
            throw new \LogicException(sprintf('The config must be either an array of options, a DSN string, null or instance of %s', AsyncAwsSnsClient::class));
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return SnsContext
     */
    public function createContext(): Context
    {
        return new SnsContext($this->establishConnection(), $this->config);
    }

    /**
     * @todo in 0.11 restore return typehint
     *
     * @return SnsAsyncClient|SnsClient
     */
    private function establishConnection()
    {
        if ($this->client) {
            return $this->client;
        }

        $config = [
            'region' => $this->config['region'],
        ];

        if (isset($this->config['endpoint'])) {
            $config['endpoint'] = $this->config['endpoint'];
        }

        if (isset($this->config['profile'])) {
            $config['profile'] = $this->config['profile'];
        }

        if ($this->config['key'] && $this->config['secret']) {
            $config['accessKeyId'] = $this->config['key'];
            $config['accessKeySecret'] = $this->config['secret'];

            if ($this->config['token']) {
                $config['sessionToken'] = $this->config['token'];
            }
        }

        return $this->client = new SnsAsyncClient(new AsyncAwsSnsClient($config));
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('sns' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf('The given scheme protocol "%s" is not supported. It must be "sns"', $dsn->getSchemeProtocol()));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'key' => $dsn->getString('key'),
            'secret' => $dsn->getString('secret'),
            'token' => $dsn->getString('token'),
            'region' => $dsn->getString('region'),
            'endpoint' => $dsn->getString('endpoint'),
            'profile' => $dsn->getString('profile'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'key' => null,
            'secret' => null,
            'token' => null,
            'region' => null,
            'endpoint' => null,
            'profile' => null,
        ];
    }
}
