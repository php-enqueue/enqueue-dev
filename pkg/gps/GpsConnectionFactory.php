<?php

namespace Enqueue\Gps;

use Google\Cloud\PubSub\PubSubClient;
use Interop\Queue\PsrConnectionFactory;

class GpsConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @see https://cloud.google.com/docs/authentication/production#providing_credentials_to_your_application
     * @see \Google\Cloud\PubSub\PubSubClient::__construct()
     *
     * [
     *   'projectId'   => The project ID from the Google Developer's Console.
     *   'keyFilePath' => The full path to your service account credentials.json file retrieved from the Google Developers Console.
     *   'retries'     => Number of retries for a failed request. **Defaults to** `3`.
     *   'scopes'      => Scopes to be used for the request.
     *   'lazy'        => 'the connection will be performed as later as possible, if the option set to true'
     * ]
     *
     * or
     *
     * gps:
     * gps:?projectId=projectName
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'gps:')
    {
        if (empty($config) || 'gps:' === $config) {
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
     * @return GpsContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new GpsContext(function () {
                return $this->establishConnection();
            });
        }

        return new GpsContext($this->establishConnection());
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if (false === strpos($dsn, 'gps:')) {
            throw new \LogicException(sprintf('The given DSN "%s" is not supported. Must start with "gps:".', $dsn));
        }

        $config = [];

        if ($query = parse_url($dsn, PHP_URL_QUERY)) {
            parse_str($query, $config);
        }

        return $config;
    }

    /**
     * @return PubSubClient
     */
    private function establishConnection()
    {
        return new PubSubClient($this->config);
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'lazy' => true,
        ];
    }
}
