<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Symfony\DriverFactoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildTransportFactoriesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        /** @var EnqueueExtension $extension */
        $extension = $container->getExtension('enqueue');
        $config = $container->resolveEnvPlaceholders($extension->getProcessedConfig(), true);

        $factories = $extension->getTransportFactories();
        
        foreach ($config['transport'] as $name => $transportConfig) {
            $factories[$name]->createConnectionFactory($container, $transportConfig);
            $factories[$name]->createContext($container, $transportConfig);

            if ($factories[$name] instanceof DriverFactoryInterface && isset($config['client'])) {
                $factories[$name]->createDriver($container, $transportConfig);
            }
        }
    }
}
