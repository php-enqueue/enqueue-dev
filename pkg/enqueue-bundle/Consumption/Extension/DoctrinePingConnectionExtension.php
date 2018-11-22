<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrinePingConnectionExtension implements MessageReceivedExtensionInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        /** @var Connection $connection */
        foreach ($this->registry->getConnections() as $connection) {
            if (!$connection->isConnected()) {
                continue;
            }

            if ($connection->ping()) {
                continue;
            }

            $context->getLogger()->debug(
                '[DoctrinePingConnectionExtension] Connection is not active trying to reconnect.'
            );

            $connection->close();
            $connection->connect();

            $context->getLogger()->debug(
                '[DoctrinePingConnectionExtension] Connection is active now.'
            );
        }
    }
}
