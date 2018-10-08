<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineClearIdentityMapExtension implements MessageReceivedExtensionInterface
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
        foreach ($this->registry->getManagers() as $name => $manager) {
            $context->getLogger()->debug(sprintf(
                '[DoctrineClearIdentityMapExtension] Clear identity map for manager "%s"',
                $name
            ));

            $manager->clear();
        }
    }
}
