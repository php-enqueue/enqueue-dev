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
     * ]
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
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
