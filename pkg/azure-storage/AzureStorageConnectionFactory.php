<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageConnectionFactory implements ConnectionFactory
{
    /**
     * @var string
     */
    protected $connectionString;

    public function __construct(string $connectionString)
    {
        $this->connectionString = $connectionString;
    }

    public function createContext(): Context
    {
        $client = QueueRestProxy::createQueueService($this->connectionString);

        return new AzureStorageContext($client);
    }
}