<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildSetupBrokerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.transports')) {
            throw new \LogicException('The "enqueue.transports" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.transports');
        $defaultName = $container->getParameter('enqueue.default_transport');

        foreach ($names as $name) {
            $diUtils = DiUtils::create(TransportFactory::MODULE, $name);

            $setupBrokerId = $diUtils->format('setup_broker');
            if (false == $container->hasDefinition($setupBrokerId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $setupBrokerId));
            }

            $tag = 'enqueue.transport.setup_broker';
            $map = [];
            foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
                foreach ($tagAttributes as $tagAttribute) {
                    $transport = $tagAttribute['transport'] ?? $defaultName;

                    if ($transport !== $name && 'all' !== $transport) {
                        continue;
                    }

                    $map[] = new Reference($serviceId);
                }
            }

            $setupBroker = $container->getDefinition($setupBrokerId);
            $setupBroker->setArgument(0, $map);
        }
    }
}
