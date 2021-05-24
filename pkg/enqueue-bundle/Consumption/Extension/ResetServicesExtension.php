<?php

namespace Enqueue\Bundle\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class ResetServicesExtension implements PostMessageReceivedExtensionInterface
{
    /**
     * @var ServicesResetter
     */
    private $resetter;

    public function __construct(ServicesResetter $resetter)
    {
        $this->resetter = $resetter;
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $context->getLogger()->debug('[ResetServicesExtension] Resetting services.');

        $this->resetter->reset();
    }
}
