<?php
namespace Enqueue\Stomp\Client;

use RabbitMq\ManagementApi\Client;

class ManagementClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @param Client|null $client
     * @param string      $vhost
     */
    public function __construct(Client $client, $vhost = '/')
    {
        $this->client = $client;
        $this->vhost = $vhost;
    }

    /**
     * @param string $vhost
     * @param string $host
     * @param int    $port
     * @param string $login
     * @param string $password
     *
     * @return ManagementClient
     */
    public static function create($vhost = '/', $host = 'localhost', $port = 15672, $login = 'guest', $password = 'guest')
    {
        return new static(new Client(null, 'http://'.$host.':'.$port, $login, $password), $vhost);
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return array
     */
    public function declareQueue($name, $options)
    {
        return $this->client->queues()->create($this->vhost, $name, $options);
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return array
     */
    public function declareExchange($name, $options)
    {
        return $this->client->exchanges()->create($this->vhost, $name, $options);
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param array  $arguments
     *
     * @return array
     */
    public function bind($exchange, $queue, $routingKey, $arguments = null)
    {
        return $this->client->bindings()->create($this->vhost, $exchange, $queue, $routingKey, $arguments);
    }
}
