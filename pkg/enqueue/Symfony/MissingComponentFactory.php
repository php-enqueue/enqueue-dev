<?php

namespace Enqueue\Symfony;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MissingComponentFactory
{
    public static function getConfiguration(string $componentName, array $packages): ArrayNodeDefinition
    {
        if (1 == count($packages)) {
            $message = sprintf(
                'In order to use the component "%s" install a package "%s"',
                $componentName,
                implode('", "', $packages)
            );
        } else {
            $message = sprintf(
                'In order to use the component "%s" install one of the packages "%s"',
                $componentName,
                implode('", "', $packages)
            );
        }

        $node = new ArrayNodeDefinition($componentName);
        $node
            ->info($message)
            ->beforeNormalization()
                ->always(function () {
                    return [];
                })
            ->end()
            ->validate()
                ->always(function () use ($message) {
                    throw new \InvalidArgumentException($message);
                })
            ->end()
        ;

        return $node;
    }
}
