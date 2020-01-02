<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;

class DoctrinePingConnectionExtension implements MessageReceivedExtensionInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
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
