<?php

namespace Enqueue;

use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;

final class ConnectionFactoryFactory implements ConnectionFactoryFactoryInterface
{
    public function create($config): ConnectionFactory
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

        $dsn = Dsn::parseFirst($config['dsn']);

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

        if ($dsn->getSchemeExtensions()) {
            foreach ($factories as $connectionClass => $info) {
                if (empty($info['supportedSchemeExtensions'])) {
                    continue;
                }

                if (false == in_array($protocol, $info['schemes'], true)) {
                    continue;
                }

                $diff = array_diff($info['supportedSchemeExtensions'], $dsn->getSchemeExtensions());
                if (empty($diff)) {
                    return $connectionClass;
                }
            }
        }

        foreach ($factories as $driverClass => $info) {
            if (false == in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            return $driverClass;
        }

        return null;
    }
}
