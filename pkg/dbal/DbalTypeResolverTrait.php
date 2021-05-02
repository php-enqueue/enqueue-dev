<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

trait DbalTypeResolverTrait
{
    protected static function resolveDbalType(string $typeName): string
    {
        static $reflection;

        $className = 'Doctrine\\DBAL\\Types\\Type';

        // Use types when using dbal v3
        if (class_exists('Doctrine\\DBAL\\Types\\Types')) {
            $className = 'Doctrine\\DBAL\\Types\\Types';
        }

        return ($reflection = $reflection ?? new \ReflectionClass($className))
            ->getConstant($typeName);
    }
}
