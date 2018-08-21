<?php

namespace Enqueue\Client;

use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Dsn\Dsn;
use Enqueue\Stomp\Client\ManagementClient;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
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
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    public function __construct(Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    public function create(PsrConnectionFactory $factory, string $dsn, array $config): DriverInterface
    {
        $dsn = new Dsn($dsn);

        if ($driverClass = $this->findDriverClass($dsn, Resources::getAvailableDrivers())) {
            if (RabbitMqDriver::class === $driverClass) {
                if (false == $factory instanceof AmqpConnectionFactory) {
                    throw new \LogicException(sprintf(
                        'The factory must be instance of "%s", got "%s"',
                        AmqpConnectionFactory::class,
                        get_class($factory)
                    ));
                }

                return new RabbitMqDriver($factory->createContext(), $this->config, $this->queueMetaRegistry);
            }

            if (RabbitMqStompDriver::class === $driverClass) {
                if (false == $factory instanceof StompConnectionFactory) {
                    throw new \LogicException(sprintf(
                        'The factory must be instance of "%s", got "%s"',
                        StompConnectionFactory::class,
                        get_class($factory)
                    ));
                }

                if (isset($config['rabbitmq_management_dsn'])) {
                    $managementDsn = new Dsn($config['rabbitmq_management_dsn']);

                    $managementClient = ManagementClient::create(
                        ltrim($managementDsn->getPath(), '/'),
                        $managementDsn->getHost(),
                        $managementDsn->getPort(),
                        $managementDsn->getUser(),
                        $managementDsn->getPassword()
                    );
                } else {
                    $managementClient = ManagementClient::create(
                        ltrim($dsn->getPath(), '/'),
                        $dsn->getHost(),
                        isset($config['management_plugin_port']) ? $config['management_plugin_port'] : 15672,
                        $dsn->getUser(),
                        $dsn->getPassword()
                    );
                }

                return new RabbitMqStompDriver($factory->createContext(), $this->config, $this->queueMetaRegistry, $managementClient);
            }

            return new $driverClass($factory->createContext(), $this->config, $this->queueMetaRegistry);
        }

        $knownDrivers = Resources::getKnownDrivers();
        if ($driverClass = $this->findDriverClass($dsn, $knownDrivers)) {
            throw new \LogicException(sprintf(
                'To use given scheme "%s" a package has to be installed. Run "composer req %s" to add it.',
                $dsn->getScheme(),
                $knownDrivers[$driverClass]['package']
            ));
        }

        throw new \LogicException(sprintf(
            'A given scheme "%s" is not supported. Maybe it is a custom driver, make sure you registered it with "%s::addDriver".',
            $dsn->getScheme(),
            Resources::class
        ));
    }

    private function findDriverClass(Dsn $dsn, array $factories): ?string
    {
        $protocol = $dsn->getSchemeProtocol();
        foreach ($factories as $driverClass => $info) {
            if (false == in_array($protocol, $info['schemes'], true)) {
                continue;
            }

            if (false == $dsn->getSchemeExtensions()) {
                return $driverClass;
            }

            if (empty($info['requiredSchemeExtensions'])) {
                continue;
            }

            $diff = array_diff($dsn->getSchemeExtensions(), $info['requiredSchemeExtensions']);
            if (empty($diff)) {
                return $driverClass;
            }
        }

        return null;
    }
}
