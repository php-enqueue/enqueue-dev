<?php

namespace Enqueue\Mongodb;

use Interop\Queue\PsrConnectionFactory;
use MongoDB\Client;

class MongodbConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $uriOptions;

    /**
     * @var array
     */
    private $driverOptions;

    public function __construct($uri = 'mongodb://127.0.0.1/', array $config = [], array $uriOptions = [], array $driverOptions = [])
    {
        $this->uri = $uri;
        $this->config = $config;
        $this->uriOptions = $uriOptions;
        $this->driverOptions = $driverOptions;
    }

    public function createContext()
    {
        $client = new Client($this->uri, $this->uriOptions, $this->driverOptions);

        return new MongodbContext($client, $this->config);
    }
}
