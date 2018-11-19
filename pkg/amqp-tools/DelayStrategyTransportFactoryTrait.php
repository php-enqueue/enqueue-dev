<?php

declare(strict_types=1);

namespace Enqueue\AmqpTools;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

trait DelayStrategyTransportFactoryTrait
{
    public function registerDelayStrategy(ContainerBuilder $container, array $config, string $factoryId, string $factoryName): void
    {
        if ($config['delay_strategy']) {
            $factory = $container->getDefinition($factoryId);

            if (false == (is_a($factory->getClass(), DelayStrategyAware::class, true) || $factory->getFactory())) {
                throw new \LogicException('Connection factory does not support delays');
            }

            if ('dlx' === strtolower($config['delay_strategy'])) {
                $delayId = sprintf('enqueue.client.%s.delay_strategy', $factoryName);
                $container->register($delayId, RabbitMqDlxDelayStrategy::class);

                $factory->addMethodCall('setDelayStrategy', [new Reference($delayId)]);
            } elseif ('delayed_message_plugin' === strtolower($config['delay_strategy'])) {
                $delayId = sprintf('enqueue.client.%s.delay_strategy', $factoryName);
                $container->register($delayId, RabbitMqDelayPluginDelayStrategy::class);

                $factory->addMethodCall('setDelayStrategy', [new Reference($delayId)]);
            } else {
                $factory->addMethodCall('setDelayStrategy', [new Reference($config['delay_strategy'])]);
            }
        }
    }
}
