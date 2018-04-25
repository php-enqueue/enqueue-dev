<?php

namespace Enqueue\Symfony;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MissingTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string[]
     */
    private $packages;

    /**
     * @param string   $name
     * @param string[] $packages
     */
    public function __construct($name, array $packages)
    {
        $this->name = $name;
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        if (1 == count($this->packages)) {
            $message = sprintf(
                'In order to use the transport "%s" install a package "%s"',
                $this->getName(),
                implode('", "', $this->packages)
            );
        } else {
            $message = sprintf(
                'In order to use the transport "%s" install one of the packages "%s"',
                $this->getName(),
                implode('", "', $this->packages)
            );
        }

        $builder
            ->info($message)
            ->beforeNormalization()
                ->always(function () {
                    return [];
                })
                ->end()
            ->validate()
                ->always(function () use ($message) {
                    throw new \InvalidArgumentException($message);
                })
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        throw new \LogicException('Should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        throw new \LogicException('Should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        throw new \LogicException('Should not be called');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
