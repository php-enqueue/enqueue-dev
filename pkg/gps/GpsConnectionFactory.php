<?php

declare(strict_types=1);

namespace Enqueue\Gps;

use Enqueue\Dsn\Dsn;
use Google\Cloud\PubSub\PubSubClient;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class GpsConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var PubSubClient
     */
    private $client;

    /**
     * @see https://cloud.google.com/docs/authentication/production#providing_credentials_to_your_application
     * @see \Google\Cloud\PubSub\PubSubClient::__construct()
     *
     * [
     *   'projectId'   => The project ID from the Google Developer's Console.
     *   'keyFilePath' => The full path to your service account credentials.json file retrieved from the Google Developers Console.
     *   'retries'     => Number of retries for a failed request. **Defaults to** `3`.
     *   'scopes'      => Scopes to be used for the request.
     *   'emulatorHost' => The endpoint used to emulate communication with GooglePubSub.
     *   'lazy'        => 'the connection will be performed as later as possible, if the option set to true'
     * ]
     *
     * or
     *
     * gps:
     * gps:?projectId=projectName
     *
     * or instance of Google\Cloud\PubSub\PubSubClient
     *
     * @param array|string|PubSubClient|null $config
     */
    public function __construct($config = 'gps:')
    {
        if ($config instanceof PubSubClient) {
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
            throw new \LogicException(sprintf('The config must be either an array of options, a DSN string, null or instance of %s', PubSubClient::class));
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return GpsContext
     */
    public function createContext(): Context
    {
        if ($this->config['lazy']) {
            return new GpsContext(function () {
                return $this->establishConnection();
            });
        }

        return new GpsContext($this->establishConnection());
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('gps' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf('The given scheme protocol "%s" is not supported. It must be "gps"', $dsn->getSchemeProtocol()));
        }

        $emulatorHost = $dsn->getString('emulatorHost');
        $hasEmulator = $emulatorHost ? true : null;

        return array_filter(array_replace($dsn->getQuery(), [
            'projectId' => $dsn->getString('projectId'),
            'keyFilePath' => $dsn->getString('keyFilePath'),
            'retries' => $dsn->getDecimal('retries'),
            'scopes' => $dsn->getString('scopes'),
            'emulatorHost' => $emulatorHost,
            'hasEmulator' => $hasEmulator,
            'lazy' => $dsn->getBool('lazy'),
        ]), function ($value) { return null !== $value; });
    }

    private function establishConnection(): PubSubClient
    {
        if (false == $this->client) {
            $this->client = new PubSubClient($this->config);
        }

        return $this->client;
    }

    private function defaultConfig(): array
    {
        return [
            'lazy' => true,
        ];
    }
}
