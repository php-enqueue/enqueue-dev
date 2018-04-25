<?php

namespace Enqueue\Bundle\Tests\Unit\Mocks;

use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TransportFactoryWithoutDriverFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'without_driver')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factoryId = 'without_driver.connection_factory';

        $container->setDefinition($factoryId, new Definition(\stdClass::class, [$config]));

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $contextId = 'without_driver.context';

        $context = new Definition(\stdClass::class, [$config]);
        $context->setPublic(true);

        $container->setDefinition($contextId, $context);

        return $contextId;
    }

    public function createDriver(ContainerBuilder $container, array $config)
    {
        throw new \LogicException('It should not be called. The method will be removed');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
