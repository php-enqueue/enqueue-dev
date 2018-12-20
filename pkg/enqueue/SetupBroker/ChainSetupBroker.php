<?php
namespace Enqueue\SetupBroker;

use Psr\Log\LoggerInterface;

class ChainSetupBroker implements SetupBrokerInterface
{
    /**
     * @var SetupBrokerInterface[]
     */
    private $setupBrokers;

    /**
     * @param SetupBrokerInterface[] $setupBrokers
     */
    public function __construct(array $setupBrokers)
    {
        $this->setupBrokers = [];

        array_walk($setupBrokers, function (SetupBrokerInterface $setupBroker) {
            $this->setupBrokers[] = $setupBroker;
        });
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        foreach ($this->setupBrokers as $setupBroker) {
            $logger->info(get_class($setupBroker));

            $setupBroker->setupBroker($logger);
        }
    }
}
