<?php

declare(strict_types=1);

namespace Enqueue\Gearman;

use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;

class GearmanConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default settings.
     *
     * [
     *     'host'  => 'localhost',
     *     'port'  => 11300
     * ]
     *
     * or
     *
     * gearman://host:port
     *
     * @param array|string $config
     */
    public function __construct($config = 'gearman:')
    {
        if (empty($config) || 'gearman:' === $config) {
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
     * @return GearmanContext
     */
    public function createContext(): PsrContext
    {
        return new GearmanContext($this->config);
    }

    private function parseDsn(string $dsn): array
    {
        $dsnConfig = parse_url($dsn);
        if (false === $dsnConfig) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ], $dsnConfig);

        if ('gearman' !== $dsnConfig['scheme']) {
            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "gearman" only.', $dsnConfig['scheme']));
        }

        return [
            'port' => $dsnConfig['port'],
            'host' => $dsnConfig['host'],
        ];
    }

    private function defaultConfig(): array
    {
        return [
            'host' => \GEARMAN_DEFAULT_TCP_HOST,
            'port' => \GEARMAN_DEFAULT_TCP_PORT,
        ];
    }
}
