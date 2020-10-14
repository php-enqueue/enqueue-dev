<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\PostConsumeExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\PreConsumeExtensionInterface;
use Psr\Log\LoggerInterface;

class DoctrineClosedEntityManagerExtension implements PreConsumeExtensionInterface, PostMessageReceivedExtensionInterface, PostConsumeExtensionInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onPreConsume(PreConsume $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function onPostConsume(PostConsume $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        if ($this->shouldBeStopped($context->getLogger())) {
            $context->interruptExecution();
        }
    }

    private function shouldBeStopped(LoggerInterface $logger): bool
    {
        foreach ($this->registry->getManagers() as $name => $manager) {
            if (!$manager instanceof EntityManagerInterface || $manager->isOpen()) {
                continue;
            }

            $logger->debug(sprintf(
                '[DoctrineClosedEntityManagerExtension] Interrupt execution as entity manager "%s" has been closed',
                $name
            ));

            return true;
        }

        return false;
    }
}
