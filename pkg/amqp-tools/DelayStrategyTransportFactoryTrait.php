<?php

namespace Enqueue\AmqpTools;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

trait DelayStrategyTransportFactoryTrait
{
    /**
     * {@inheritdoc}
     */
    public function registerDelayStrategy(ContainerBuilder $container, array $config, $factoryId, $factoryName)
    {
        if ($config['delay_strategy']) {
            $factory = $container->getDefinition($factoryId);

            if (false == is_a($factory->getClass(), DelayStrategyAware::class, true)) {
                throw new \LogicException('Connection factory does not support delays');
            }

            if (strtolower($config['delay_strategy']) === 'dlx') {
                $delayId = sprintf('enqueue.client.%s.delay_strategy', $factoryName);
                $container->register($delayId, RabbitMqDlxDelayStrategy::class);

                $factory->addMethodCall('setDelayStrategy', [new Reference($delayId)]);
            } elseif (strtolower($config['delay_strategy']) === 'delayed_message_plugin') {
                $delayId = sprintf('enqueue.client.%s.delay_strategy', $factoryName);
                $container->register($delayId, RabbitMqDelayPluginDelayStrategy::class);

                $factory->addMethodCall('setDelayStrategy', [new Reference($delayId)]);
            } else {
                $factory->addMethodCall('setDelayStrategy', [new Reference($config['delay_strategy'])]);
            }
        }
    }
}
