<?php

namespace Enqueue;

use Enqueue\Dsn\Dsn;
use Interop\Queue\PsrConnectionFactory;

class ConnectionFactoryFactory
{
    /**
     * @param string
     *
     * @return PsrConnectionFactory
     */
    public function create(string $dsn): PsrConnectionFactory
    {
        $dsn = new Dsn($dsn);

        $availableSchemes = Resources::getAvailableSchemes();

        if (false == array_key_exists($dsn->getScheme(), $availableSchemes)) {
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

        $dsnSchemeExtensions = $dsn->getSchemeExtensions();
        if (false == $dsnSchemeExtensions) {
            $factoryClass = $availableSchemes[$dsn->getScheme()];

            return new $factoryClass((string) $dsn);
        }

        $protocol = $dsn->getSchemeProtocol();
        foreach ($availableSchemes as $driverClass => $info) {
            if (false == in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            if (empty($info['supportedSchemeExtensions'])) {
                continue;
            }

            $diff = array_diff($dsnSchemeExtensions, $info['supportedSchemeExtensions']);
            if (empty($diff)) {
                $factoryClass = $availableSchemes[$dsn->getScheme()];

                return new $factoryClass((string) $dsn);
            }
        }

        throw new \LogicException(sprintf('There is no factory that supports scheme "%s"', $dsn->getScheme()));
    }
}
