<?php

namespace Enqueue;

use Enqueue\Psr\PsrConnectionFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConnectionFactoryFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string[]
     */
    private $nameToFactoryClassMap;

    /**
     * @param string[] $nameToFactoryClassMap
     */
    public function __construct(array $nameToFactoryClassMap)
    {
        $this->nameToFactoryClassMap = $nameToFactoryClassMap;
    }

    /**
     * @param string|array $config
     *
     * @return PsrConnectionFactory
     */
    public function createFactory($config)
    {
        if (is_string($config)) {
            if (false !== strpos($config, 'doctrine://')) {
                list(, $connectionName) = explode('://', 2);

                $factoryClass = $this->findFactoryClass('doctrine');

                return new $factoryClass(
                    $this->container->get('doctrine'),
                    ['connection_name' => $connectionName]
                );
            }

            return dsn_to_connection_factory($config, function () {
                return dsn_connection_factory_map();
            });
        }

        if (is_array($config)) {
            if (false == array_key_exists('factory', $config)) {
                throw new \LogicException('The config must have a "factory" option set');
            }

            $factoryClass = $this->findFactoryClass('doctrine');

            if ('doctrine' == $config['factory']) {
                return new $factoryClass($this->container->get('doctrine'), $config);
            }

            return new $factoryClass($config);
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function findFactoryClass($name)
    {
        if (false == array_key_exists($name, $this->nameToFactoryClassMap)) {
            throw new \LogicException(sprintf('The factory for given name "%s" does not exist', $name));
        }

        return $this->nameToFactoryClassMap[$name];
    }
}
