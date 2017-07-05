<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

        if (false == $container->hasDefinition('enqueue.events.registry')) {
            return;
        }

        $registeredToEvent = [];
        foreach ($container->findTaggedServiceIds('kernel.event_listener') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['async'])) {
                    continue;
                }

                $event = $tagAttribute['event'];

                $service = $container->getDefinition($serviceId);

                $service->clearTag('kernel.event_listener');
                $service->addTag('enqueue.async_event_listener', $tagAttribute);

                if (false == isset($registeredToEvent[$event])) {
                    $container->getDefinition('enqueue.events.async_listener')
                        ->addTag('kernel.event_listener', [
                            'event' => $event,
                            'method' => 'onEvent',
                        ])
                    ;

                    $container->getDefinition('enqueue.events.async_processor')
                        ->addTag('enqueue.client.processor', [
                            'topicName' => 'event.'.$event,
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
                $service->clearTag('kernel.event_subscriber');
                $service->addTag('enqueue.async_event_subscriber', $tagAttribute);

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

                        $container->getDefinition('enqueue.events.async_processor')
                            ->addTag('enqueue.client.processor', [
                                'topicName' => 'event.'.$event,
                            ])
                        ;

                        $registeredToEvent[$event] = true;
                    }
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
