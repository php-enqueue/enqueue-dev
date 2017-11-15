<?php

namespace Enqueue\Symfony;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\Symfony\GpsTransportFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Interop\Amqp\AmqpConnectionFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Enqueue\dsn_to_connection_factory;

class DefaultTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->beforeNormalization()
                ->always(function ($v) {
                    if (is_array($v)) {
                        if (empty($v['dsn']) && empty($v['alias'])) {
                            throw new \LogicException('Either dsn or alias option must be set');
                        }

                        return $v;
                    }

                    if (empty($v)) {
                        return ['dsn' => 'null:'];
                    }

                    if (is_string($v)) {
                        return false !== strpos($v, '://') || false !== strpos($v, 'env_') ?
                            ['dsn' => $v] :
                            ['alias' => $v];
                    }
                })
            ->end()
            ->children()
                ->scalarNode('alias')->cannotBeEmpty()->end()
                ->scalarNode('dsn')->cannotBeEmpty()->end()
            ->end()
        ->end()
        ;
    }

    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.connection_factory', $config['alias']);
        } else {
            $dsn = $this->resolveDSN($container, $config['dsn']);

            $aliasId = $this->findFactory($dsn)->createConnectionFactory($container, $config);
        }

        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        $container->setAlias($factoryId, new Alias($aliasId, true));
        $container->setAlias('enqueue.transport.connection_factory', new Alias($factoryId, true));

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.context', $config['alias']);
        } else {
            $dsn = $this->resolveDSN($container, $config['dsn']);

            $aliasId = $this->findFactory($dsn)->createContext($container, $config);
        }

        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());

        $container->setAlias($contextId, new Alias($aliasId, true));
        $container->setAlias('enqueue.transport.context', new Alias($contextId, true));

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.client.%s.driver', $config['alias']);
        } else {
            $dsn = $this->resolveDSN($container, $config['dsn']);

            $aliasId = $this->findFactory($dsn)->createDriver($container, $config);
        }

        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());

        $container->setAlias($driverId, new Alias($aliasId, true));
        $container->setAlias('enqueue.client.driver', new Alias($driverId, true));

        return $driverId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * This is a quick fix to the exception "Incompatible use of dynamic environment variables "ENQUEUE_DSN" found in parameters."
     * TODO: We'll have to come up with a better solution.
     *
     * @param ContainerBuilder $container
     * @param $dsn
     *
     * @return array|false|string
     */
    private function resolveDSN(ContainerBuilder $container, $dsn)
    {
        if (method_exists($container, 'resolveEnvPlaceholders')) {
            $dsn = $container->resolveEnvPlaceholders($dsn);

            $matches = [];
            if (preg_match('/%env\((.*?)\)/', $dsn, $matches)) {
                if (false === $realDsn = getenv($matches[1])) {
                    throw new \LogicException(sprintf('The env "%s" var is not defined', $matches[1]));
                }

                return $realDsn;
            }
        }

        return $dsn;
    }

    /**
     * @param string
     * @param mixed $dsn
     *
     * @return TransportFactoryInterface
     */
    private function findFactory($dsn)
    {
        $factory = dsn_to_connection_factory($dsn);

        if ($factory instanceof AmqpConnectionFactory) {
            return new AmqpTransportFactory('default_amqp');
        }

        if ($factory instanceof FsConnectionFactory) {
            return new FsTransportFactory('default_fs');
        }

        if ($factory instanceof DbalConnectionFactory) {
            return new DbalTransportFactory('default_dbal');
        }

        if ($factory instanceof NullConnectionFactory) {
            return new NullTransportFactory('default_null');
        }

        if ($factory instanceof GpsConnectionFactory) {
            return new GpsTransportFactory('default_gps');
        }

        if ($factory instanceof RedisConnectionFactory) {
            return new RedisTransportFactory('default_redis');
        }

        if ($factory instanceof SqsConnectionFactory) {
            return new SqsTransportFactory('default_sqs');
        }

        if ($factory instanceof StompConnectionFactory) {
            return new StompTransportFactory('default_stomp');
        }

        throw new \LogicException(sprintf(
            'There is no supported transport factory for the connection factory "%s" created from DSN "%s"',
            get_class($factory),
            $dsn
        ));
    }
}
