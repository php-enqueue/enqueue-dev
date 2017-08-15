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
