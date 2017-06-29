<?php

namespace Enqueue\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface DriverFactoryInterface
{
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
