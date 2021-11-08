<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use ErrorException;
use Throwable;

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

            if ($this->ping($connection)) {
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

    private function ping(Connection $connection): bool
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            $dummySelectSQL = $connection->getDatabasePlatform()->getDummySelectSQL();

            $connection->executeQuery($dummySelectSQL);

            return true;
        } catch (Throwable $exception) {
            return false;
        } finally {
            restore_error_handler();
        }
    }
}
