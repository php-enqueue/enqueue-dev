<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel;

class AsyncEventsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasDefinition('enqueue.events.async_listener')) {
            return;
        }

        if (false == $container->hasDefinition('enqueue.events.registry')) {
            return;
        }

        $defaultClient = $container->getParameter('enqueue.default_client');

        // TODO: Remove when dropping Symfony < 5.3
        $useLegacyDispatcherConfig = (Kernel::VERSION_ID < 50300);

        $registeredToEvent = [];
        foreach ($container->findTaggedServiceIds('kernel.event_listener') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['async'])) {
                    continue;
                }

                $event = $tagAttribute['event'];

                $service = $container->getDefinition($serviceId);

                if ($useLegacyDispatcherConfig) {
                    $service->clearTag('kernel.event_listener');
                    $service->addTag('enqueue.async_event_listener', $tagAttribute);
                }

                if (false == isset($registeredToEvent[$event])) {
                    $container->getDefinition('enqueue.events.async_listener')
                        ->addTag('kernel.event_listener', [
                            'event' => $event,
                            'method' => 'onEvent',
                        ])
                    ;

                    if (!$useLegacyDispatcherConfig) {
                        $container->getDefinition('enqueue.events.async_listener')
                            ->addTag('kernel.event_listener', [
                                'event' => $event,
                                'method' => 'onEvent',
                                'dispatcher' => 'enqueue.events.event_dispatcher',
                            ])
                        ;
                    }

                    $container->getDefinition('enqueue.events.async_processor')
                        ->addTag('enqueue.processor', [
                            'topic' => 'event.'.$event,
                            'client' => $defaultClient,
                        ])
                    ;

                    $registeredToEvent[$event] = true;
                }
            }
        }

        foreach ($container->findTaggedServiceIds('kernel.event_subscriber') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['async'])) {
                    continue;
                }

                $service = $container->getDefinition($serviceId);

                if ($useLegacyDispatcherConfig) {
                    $service->clearTag('kernel.event_subscriber');
                    $service->addTag('enqueue.async_event_subscriber', $tagAttribute);
                }

                /** @var EventSubscriberInterface $serviceClass */
                $serviceClass = $service->getClass();

                foreach ($serviceClass::getSubscribedEvents() as $event => $data) {
                    if (false == isset($registeredToEvent[$event])) {
                        $container->getDefinition('enqueue.events.async_listener')
                            ->addTag('kernel.event_listener', [
                                'event' => $event,
                                'method' => 'onEvent',
                            ])
                        ;

                        if (!$useLegacyDispatcherConfig) {
                            $container->getDefinition('enqueue.events.async_listener')
                                ->addTag('kernel.event_listener', [
                                    'event' => $event,
                                    'method' => 'onEvent',
                                    'dispatcher' => 'enqueue.events.event_dispatcher',
                                ])
                            ;
                        }

                        $container->getDefinition('enqueue.events.async_processor')
                            ->addTag('enqueue.processor', [
                                'topicName' => 'event.'.$event,
                                'client' => $defaultClient,
                            ])
                        ;

                        $registeredToEvent[$event] = true;
                    }
                }
            }
        }

        if ($useLegacyDispatcherConfig) {
            $registerListenersPass = new RegisterListenersPass(
                'enqueue.events.event_dispatcher',
                'enqueue.async_event_listener',
                'enqueue.async_event_subscriber'
            );
            $registerListenersPass->process($container);
        }
    }
}
