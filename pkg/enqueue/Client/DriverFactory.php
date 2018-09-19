<?php

namespace Enqueue\Client;

use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\StompManagementClient;
use Enqueue\Dsn\Dsn;
use Enqueue\Stomp\StompConnectionFactory;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Queue\PsrConnectionFactory;

final class DriverFactory implements DriverFactoryInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    public function __construct(Config $config, RouteCollection $routeCollection)
    {
        $this->config = $config;
        $this->routeCollection = $routeCollection;
    }

    public function create(PsrConnectionFactory $factory, string $dsn, array $config): DriverInterface
    {
        $dsn = new Dsn($dsn);

        if ($driverInfo = $this->findDriverInfo($dsn, Resources::getAvailableDrivers())) {
            $driverClass = $driverInfo['factoryClass'];

            if (RabbitMqDriver::class === $driverClass) {
                if (false == $factory instanceof AmqpConnectionFactory) {
                    throw new \LogicException(sprintf(
                        'The factory must be instance of "%s", got "%s"',
                        AmqpConnectionFactory::class,
                        get_class($factory)
                    ));
                }

                return new RabbitMqDriver($factory->createContext(), $this->config, $this->routeCollection);
            }

            if (RabbitMqStompDriver::class === $driverClass) {
                if (false == $factory instanceof StompConnectionFactory) {
                    throw new \LogicException(sprintf(
                        'The factory must be instance of "%s", got "%s"',
                        StompConnectionFactory::class,
                        get_class($factory)
                    ));
                }

                $managementClient = StompManagementClient::create(
                    ltrim($dsn->getPath(), '/'),
                    $dsn->getHost() ?: 'localhost',
                    $config['management_plugin_port'] ?? 15672,
                    (string) $dsn->getUser(),
                    (string) $dsn->getPassword()
                );

                return new RabbitMqStompDriver($factory->createContext(), $this->config, $this->routeCollection, $managementClient);
            }

            return new $driverClass($factory->createContext(), $this->config, $this->routeCollection);
        }

        $knownDrivers = Resources::getKnownDrivers();
        if ($driverInfo = $this->findDriverInfo($dsn, $knownDrivers)) {
            throw new \LogicException(sprintf(
                'To use given scheme "%s" a package has to be installed. Run "composer req %s" to add it.',
                $dsn->getScheme(),
                implode(' ', $driverInfo['packages'])
            ));
        }

        throw new \LogicException(sprintf(
            'A given scheme "%s" is not supported. Maybe it is a custom driver, make sure you registered it with "%s::addDriver".',
            $dsn->getScheme(),
            Resources::class
        ));
    }

    private function findDriverInfo(Dsn $dsn, array $factories): ?array
    {
        $protocol = $dsn->getSchemeProtocol();

        if ($dsn->getSchemeExtensions()) {
            foreach ($factories as $info) {
                if (empty($info['requiredSchemeExtensions'])) {
                    continue;
                }

                if (false == in_array($protocol, $info['schemes'], true)) {
                    continue;
                }

                $diff = array_diff($dsn->getSchemeExtensions(), $info['requiredSchemeExtensions']);
                if (empty($diff)) {
                    return $info;
                }
            }
        }

        foreach ($factories as $driverClass => $info) {
            if (false == empty($info['requiredSchemeExtensions'])) {
                continue;
            }

            if (false == in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            return $info;
        }

        return null;
    }
}
