<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

class WampConnectionFactory implements ConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to Ratchet localhost.
     *
     * $config = [
     *   'dsn'                 => 'wamp://127.0.0.1:9090',
     *   'host'                => '127.0.0.1',
     *   'port'                => '9090',
     *   'max_retries'         => 15,
     *   'initial_retry_delay' => 1.5,
     *   'max_retry_delay'     => 300,
     *   'retry_delay_growth'  => 1.5,
     * ]
     *
     * or
     *
     * wamp://127.0.0.1:9090?max_retries=10
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'wamp:')
    {
        if (empty($config)) {
            $config = $this->parseDsn('wamp:');
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $config = empty($config['dsn']) ? $config : $this->parseDsn($config['dsn']);
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $config = array_replace([
            'host' => '127.0.0.1',
            'port' => '9090',
            'max_retries' => 15,
            'initial_retry_delay' => 1.5,
            'max_retry_delay' => 300,
            'retry_delay_growth' => 1.5,
        ], $config);

        $this->config = $config;
    }

    public function createContext(): Context
    {
        return new WampContext(function () {
            return $this->establishConnection();
        });
    }

    private function establishConnection(): Client
    {
        $uri = sprintf('ws://%s:%s', $this->config['host'], $this->config['port']);

        $client = new Client('realm1');
        $client->addTransportProvider(new PawlTransportProvider($uri));
        $client->setReconnectOptions([
            'max_retries' => $this->config['max_retries'],
            'initial_retry_delay' => $this->config['initial_retry_delay'],
            'max_retry_delay' => $this->config['max_retry_delay'],
            'retry_delay_growth' => $this->config['retry_delay_growth'],
        ]);

        return $client;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if (false === in_array($dsn->getSchemeProtocol(), ['wamp', 'ws'], true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "wamp"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'max_retries' => $dsn->getDecimal('max_retries'),
            'initial_retry_delay' => $dsn->getFloat('initial_retry_delay'),
            'max_retry_delay' => $dsn->getDecimal('max_retry_delay'),
            'retry_delay_growth' => $dsn->getFloat('retry_delay_growth'),
        ]), function ($value) { return null !== $value; });
    }
}
