<?php

namespace Enqueue\Symfony;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface TransportFactoryInterface
{
    /**
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(ArrayNodeDefinition $builder);

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return string The method must return a context service id
     */
    public function createContext(ContainerBuilder $container, array $config);

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return string The method must return a driver service id
     */
    public function createDriver(ContainerBuilder $container, array $config);

    /**
     * @return string
     */
    public function getName();
}
