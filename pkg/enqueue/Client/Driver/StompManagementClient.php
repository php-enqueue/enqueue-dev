<?php

namespace Enqueue\Client\Driver;

use RabbitMq\ManagementApi\Client;

class StompManagementClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $vhost;

    public function __construct(Client $client, string $vhost = '/')
    {
        $this->client = $client;
        $this->vhost = $vhost;
    }

    public static function create(string $vhost = '/', string $host = 'localhost', int $port = 15672, string $login = 'guest', string $password = 'guest'): self
    {
        return new static(new Client(null, 'http://'.$host.':'.$port, $login, $password), $vhost);
    }

    public function declareQueue(string $name, array $options)
    {
        return $this->client->queues()->create($this->vhost, $name, $options);
    }

    public function declareExchange(string $name, array $options)
    {
        return $this->client->exchanges()->create($this->vhost, $name, $options);
    }

    public function bind(string $exchange, string $queue, string $routingKey = null, $arguments = null)
    {
        return $this->client->bindings()->create($this->vhost, $exchange, $queue, $routingKey, $arguments);
    }
}
