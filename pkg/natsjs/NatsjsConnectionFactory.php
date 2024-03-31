<?php

/*
    Copyright (c) 2024 g41797
    SPDX-License-Identifier: MIT
*/

namespace Enqueue\Natsjs;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class NatsjsConnectionFactory implements ConnectionFactory
{
    private Client $broker;
    private array $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default settings.
     * see defaultConfig() and parseDsn()).
     *
     * @param array|string $config
     */
    public function __construct($config = 'nats:')
    {
        if (empty($config) || 'nats:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    public function createContext(): Context
    {
        return new NatsjsContext($this->createBroker());
    }

    private function defaultConfig(): array
    {
        return [
            'scheme' => 'nats',
            'host' => 'localhost',
            'port' => 4222,
            'user' => null,
            'pass' => null,
            'pedantic' => false,
            'reconnect' => true,
        ];
    }

    /**
     * format of dns string for nats:
     * 'nats://username:password@hostname:port'
     * part of components may be omitted
     * 'nats:'      connect to 'localhost:4022'
     */
    private function parseDsn(string $dsn): array
    {
        $dsnConfig = parse_url($dsn);

        if (false === $dsnConfig) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace($this->defaultConfig(), $dsnConfig);

        if ('nats' !== $dsnConfig['scheme']) {
            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "nats" only.', $dsnConfig['scheme']));
        }

        return $dsnConfig;
    }

    private function createBroker(): Client
    {
        if ($this->broker) {
            return $this->broker;
        }

        $this->broker = new Client(new Configuration($this->config));

        return $this->broker;
    }
}
