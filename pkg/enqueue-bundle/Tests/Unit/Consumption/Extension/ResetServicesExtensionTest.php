<?php

namespace Enqueue\Bundle\Tests\Unit\Consumption\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Enqueue\Bundle\Consumption\Extension\ResetServicesExtension;
use Enqueue\Consumption\Context\PostMessageReceived;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class ResetServicesExtensionTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new ResetServicesExtension($this->createResetterMock());
    }

    public function testItShouldResetServices()
    {
        $resetter = $this->createResetterMock();
        $resetter
            ->expects($this->once())
            ->method('reset')
        ;

        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[ResetServicesExtension] Resetting services.')
        ;

        $extension = new ResetServicesExtension($resetter);
        $extension->onPostMessageReceived($context);
    }

    protected function createContext(): PostMessageReceived
    {
        return new PostMessageReceived(
            $this->createMock(InteropContext::class),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            $this->createMock(Processor::class),
            1,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    protected function createResetterMock(): ServicesResetter
    {
        return $this->createMock(ServicesResetter::class);
    }
}
