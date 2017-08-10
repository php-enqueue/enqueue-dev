<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Enqueue\AmqpTools\DelayStrategyTransportFactoryTrait;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DelayStrategyTransportFactoryTraitTest extends TestCase
{
    public function testShouldRegisterDlxStrategy()
    {
        $container = new ContainerBuilder();
        $container->register('factoryId', DelayStrategyTransportFactoryImpl::class);

        $trait = new DelayStrategyTransportFactoryTraitImpl();
        $trait->registerDelayStrategy($container, ['delay_strategy' => 'dlx'], 'factoryId', 'name');

        $factory = $container->getDefinition('factoryId');

        $calls = $factory->getMethodCalls();

        $this->assertSame('setDelayStrategy', $calls[0][0]);
        $this->assertInstanceOf(Reference::class, $calls[0][1][0]);
        $this->assertSame('enqueue.client.name.delay_strategy', (string) $calls[0][1][0]);

        $strategy = $container->getDefinition('enqueue.client.name.delay_strategy');

        $this->assertSame(RabbitMqDlxDelayStrategy::class, $strategy->getClass());
    }

    public function testShouldRegisterDelayMessagePluginStrategy()
    {
        $container = new ContainerBuilder();
        $container->register('factoryId', DelayStrategyTransportFactoryImpl::class);

        $trait = new DelayStrategyTransportFactoryTraitImpl();
        $trait->registerDelayStrategy($container, ['delay_strategy' => 'delayed_message_plugin'], 'factoryId', 'name');

        $factory = $container->getDefinition('factoryId');

        $calls = $factory->getMethodCalls();

        $this->assertSame('setDelayStrategy', $calls[0][0]);
        $this->assertInstanceOf(Reference::class, $calls[0][1][0]);
        $this->assertSame('enqueue.client.name.delay_strategy', (string) $calls[0][1][0]);

        $strategy = $container->getDefinition('enqueue.client.name.delay_strategy');

        $this->assertSame(RabbitMqDelayPluginDelayStrategy::class, $strategy->getClass());
    }

    public function testShouldRegisterDelayStrategyService()
    {
        $container = new ContainerBuilder();
        $container->register('factoryId', DelayStrategyTransportFactoryImpl::class);

        $trait = new DelayStrategyTransportFactoryTraitImpl();
        $trait->registerDelayStrategy($container, ['delay_strategy' => 'service_name'], 'factoryId', 'name');

        $factory = $container->getDefinition('factoryId');

        $calls = $factory->getMethodCalls();

        $this->assertSame('setDelayStrategy', $calls[0][0]);
        $this->assertInstanceOf(Reference::class, $calls[0][1][0]);
        $this->assertSame('service_name', (string) $calls[0][1][0]);
    }
}

class DelayStrategyTransportFactoryTraitImpl
{
    use DelayStrategyTransportFactoryTrait;
}

class DelayStrategyTransportFactoryImpl implements DelayStrategyAware
{
    use DelayStrategyAwareTrait;
}
