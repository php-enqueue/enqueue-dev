<?php

namespace Enqueue;

use Enqueue\Dsn\Dsn;
use Interop\Queue\PsrConnectionFactory;

final class ConnectionFactoryFactory implements ConnectionFactoryFactoryInterface
{
    public function create($config): PsrConnectionFactory
    {
        if (is_string($config)) {
            $config = ['dsn' => $config];
        }

        if (false == is_array($config)) {
            throw new \InvalidArgumentException('The config must be either array or DSN string.');
        }

        if (false == array_key_exists('dsn', $config)) {
            throw new \InvalidArgumentException('The config must have dsn key set.');
        }

        $dsn = new Dsn($config['dsn']);

        if ($factoryClass = $this->findFactoryClass($dsn, Resources::getAvailableConnections())) {
            return new $factoryClass(1 === count($config) ? $config['dsn'] : $config);
        }

        $knownConnections = Resources::getKnownConnections();
        if ($factoryClass = $this->findFactoryClass($dsn, $knownConnections)) {
            throw new \LogicException(sprintf(
                'To use given scheme "%s" a package has to be installed. Run "composer req %s" to add it.',
                $dsn->getScheme(),
                $knownConnections[$factoryClass]['package']
            ));
        }

        throw new \LogicException(sprintf(
            'A given scheme "%s" is not supported. Maybe it is a custom connection, make sure you registered it with "%s::addConnection".',
            $dsn->getScheme(),
            Resources::class
        ));
    }

    private function findFactoryClass(Dsn $dsn, array $factories): ?string
    {
        $protocol = $dsn->getSchemeProtocol();
        foreach ($factories as $connectionClass => $info) {
            if (false == in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            if (false == $dsn->getSchemeExtensions()) {
                return $connectionClass;
            }

            if (empty($info['supportedSchemeExtensions'])) {
                continue;
            }

            $diff = array_diff($dsn->getSchemeExtensions(), $info['supportedSchemeExtensions']);
            if (empty($diff)) {
                return $connectionClass;
            }
        }

        return null;
    }
}
