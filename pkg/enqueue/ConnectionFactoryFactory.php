<?php

namespace Enqueue;

use Enqueue\Dsn\Dsn;
use Interop\Queue\PsrConnectionFactory;

class ConnectionFactoryFactory
{
    /**
     * @param string|array $config
     *
     * @return PsrConnectionFactory
     */
    public function create($config): PsrConnectionFactory
    {
        if (is_string($config)) {
            $config = ['dsn' => $config];
        }

        if (false == is_array($config)) {
            throw new \InvalidArgumentException(sprintf('Config must be either string or array. Got %s', gettype($config)));
        }

        if (false == array_key_exists('dsn', $config)) {
            throw new \InvalidArgumentException('The config must have dsn field set.');
        }

        $dsn = new Dsn($config['dsn']);

        $availableSchemes = Resources::getAvailableSchemes();

        if (array_key_exists($dsn->getScheme(), $availableSchemes)) {
            $factoryClass = $availableSchemes[$dsn->getScheme()];

            return new $factoryClass($config);
        }

        $knownSchemes = Resources::getKnownSchemes();
        if (array_key_exists($dsn->getScheme(), $knownSchemes)) {
            $knownConnections = Resources::getKnownConnections();

            throw new \LogicException(sprintf(
                'A transport "%s" is not installed. Run "composer req %s" to add it.',
                $knownSchemes[$dsn->getScheme()],
                $knownConnections['package']
            ));
        }

        throw new \LogicException(sprintf(
            'A transport is not known. Make sure you registered it with "%s" if it is custom one.',
            Resources::class
        ));
    }
}
