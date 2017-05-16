<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class AsyncEventsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false == $container->hasDefinition('enqueue.events.async_listener')) {
            return;
        }

        $registeredToEvent = [];
        foreach ($container->findTaggedServiceIds('kernel.event_listener') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['async'])) {
                    continue;
                }

                $service = $container->getDefinition($serviceId);

                $service->clearTag('kernel.event_listener');
                $service->addTag('enqueue.async_event_listener', $tagAttribute);

                if (false == isset($registeredToEvent[$tagAttribute['event']])) {
                    $container->getDefinition('enqueue.events.async_listener')
                        ->addTag('kernel.event_listener', [
                            'event' => $tagAttribute['event'],
                            'method' => 'onEvent',
                        ])
                    ;

                    $registeredToEvent[$tagAttribute['event']] = true;
                }
            }
        }

        $registerListenersPass = new RegisterListenersPass(
            'enqueue.events.event_dispatcher',
            'enqueue.async_event_listener',
            'enqueue.async_event_subscriber'
        );
        $registerListenersPass->process($container);
    }
}
