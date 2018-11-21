<?php

namespace Enqueue\Client;

use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\StompManagementClient;
use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;

final class DriverFactory implements DriverFactoryInterface
{
    public function create(ConnectionFactory $factory, Config $config, RouteCollection $collection): DriverInterface
    {
        $dsn = $config->getTransportOption('dsn');

        if (empty($dsn)) {
            throw new \LogicException('This driver factory relies on dsn option from transport config. The option is empty or not set.');
        }

        $dsn = Dsn::parseFirst($dsn);

        if ($driverInfo = $this->findDriverInfo($dsn, Resources::getAvailableDrivers())) {
            $driverClass = $driverInfo['driverClass'];

            if (RabbitMqStompDriver::class === $driverClass) {
                return $this->createRabbitMqStompDriver($factory, $dsn, $config, $collection);
            }

            return new $driverClass($factory->createContext(), $config, $collection);
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

    private function createRabbitMqStompDriver(ConnectionFactory $factory, Dsn $dsn, Config $config, RouteCollection $collection): RabbitMqStompDriver
    {
        $defaultManagementHost = $dsn->getHost() ?: $config->getTransportOption('host', 'localhost');
        $managementVast = ltrim($dsn->getPath(), '/') ?: $config->getTransportOption('vhost', '/');

        $managementClient = StompManagementClient::create(
            urldecode($managementVast),
            $config->getDriverOption('rabbitmq_management_host', $defaultManagementHost),
            $config->getDriverOption('rabbitmq_management_port', 15672),
            (string) $dsn->getUser() ?: $config->getTransportOption('user', 'guest'),
            (string) $dsn->getPassword() ?: $config->getTransportOption('pass', 'guest')
        );

        return new RabbitMqStompDriver($factory->createContext(), $config, $collection, $managementClient);
    }
}
