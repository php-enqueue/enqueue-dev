<?php

namespace Enqueue\Client;

use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Dsn\Dsn;
use Enqueue\Stomp\Client\ManagementClient;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
use Interop\Queue\PsrConnectionFactory;

class DriverFactory
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
        $protocol = $dsn->getSchemeProtocol();
        $extensions = $dsn->getSchemeExtensions();

        $foundDriverClass = null;
        foreach (Resources::getAvailableDrivers() as $driverClass => $info) {
            if (false == in_array($protocol, $info['supportedSchemeProtocols'], true)) {
                continue;
            }

            if ($info['requiredSchemeExtensions']) {
                if (empty($extensions)) {
                    continue;
                }

                foreach ($info['requiredSchemeExtensions'] as $requiredSchemeExtension) {
                    if (false == in_array($requiredSchemeExtension, $extensions, true)) {
                        continue;
                    }
                }

                $foundDriverClass = $driverClass;

                break;
            }

            $foundDriverClass = $driverClass;
            break;
        }

        if (false == $foundDriverClass) {
            throw new \LogicException(sprintf('The driver class that supports scheme %s could not be found', $dsn->getScheme()));
        }

        if (RabbitMqDriver::class === $foundDriverClass) {
            return new RabbitMqDriver($factory->createContext(), $this->config, $this->queueMetaRegistry);
        }

        if (RabbitMqStompDriver::class === $foundDriverClass) {
            $managementClient = ManagementClient::create(
                ltrim($dsn->getPath(), '/'),
                $dsn->getHost(),
                $config['management_plugin_port'],
                $dsn->getUser(),
                $dsn->getPassword()
            );

            return new RabbitMqStompDriver($factory->createContext(), $this->config, $this->queueMetaRegistry, $managementClient);
        }

        return new $foundDriverClass($factory->createContext(), $this->config, $this->queueMetaRegistry);
    }
}
