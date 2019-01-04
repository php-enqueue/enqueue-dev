<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

use Enqueue\Dsn\Dsn;

class GenericStatsStorageFactory implements StatsStorageFactory
{
    public function create($config): StatsStorage
    {
        if (\is_string($config)) {
            $config = ['dsn' => $config];
        }

        if (false === \is_array($config)) {
            throw new \InvalidArgumentException('The config must be either array or DSN string.');
        }

        if (false === array_key_exists('dsn', $config)) {
            throw new \InvalidArgumentException('The config must have dsn key set.');
        }

        $dsn = Dsn::parseFirst($config['dsn']);

        if ($storageClass = $this->findStorageClass($dsn, Resources::getKnownStorages())) {
            return new $storageClass(1 === \count($config) ? $config['dsn'] : $config);
        }

        throw new \LogicException(sprintf('A given scheme "%s" is not supported.', $dsn->getScheme()));
    }

    private function findStorageClass(Dsn $dsn, array $factories): ?string
    {
        $protocol = $dsn->getSchemeProtocol();

        if ($dsn->getSchemeExtensions()) {
            foreach ($factories as $storageClass => $info) {
                if (empty($info['supportedSchemeExtensions'])) {
                    continue;
                }

                if (false === \in_array($protocol, $info['schemes'], true)) {
                    continue;
                }

                $diff = array_diff($info['supportedSchemeExtensions'], $dsn->getSchemeExtensions());
                if (empty($diff)) {
                    return $storageClass;
                }
            }
        }

        foreach ($factories as $storageClass => $info) {
            if (false === \in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            return $storageClass;
        }

        return null;
    }
}
