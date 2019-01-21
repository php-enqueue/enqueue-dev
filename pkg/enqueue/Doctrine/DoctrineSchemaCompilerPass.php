<?php

namespace Enqueue\Doctrine;

use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineSchemaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('doctrine')) {
            return;
        }

        foreach ($container->getParameter('enqueue.transports') as $name) {
            $diUtils = DiUtils::create(TransportFactory::MODULE, $name);

            $container->register($diUtils->format('connection_factory_factory.outer'), DoctrineConnectionFactoryFactory::class)
                ->setDecoratedService($diUtils->format('connection_factory_factory'), $diUtils->format('connection_factory_factory.inner'))
                ->addArgument(new Reference('doctrine'))
                ->addArgument(new Reference($diUtils->format('connection_factory_factory.inner')))
            ;
        }

        foreach ($container->getParameter('enqueue.clients') as $name) {
            $diUtils = DiUtils::create(ClientFactory::MODULE, $name);

            $container->register($diUtils->format('driver_factory.outer'), DoctrineDriverFactory::class)
                ->setDecoratedService($diUtils->format('driver_factory'), $diUtils->format('driver_factory.inner'))
                ->addArgument(new Reference($diUtils->format('driver_factory.inner')))
            ;
        }
    }
}
