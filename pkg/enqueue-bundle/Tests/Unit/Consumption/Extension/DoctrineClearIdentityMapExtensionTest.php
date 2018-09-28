<?php

namespace Enqueue\Bundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Enqueue\Bundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Enqueue\Consumption\Context;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineClearIdentityMapExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DoctrineClearIdentityMapExtension($this->createRegistryMock());
    }

    public function testShouldClearIdentityMap()
    {
        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('clear')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getManagers')
            ->will($this->returnValue(['manager-name' => $manager]))
        ;

        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[DoctrineClearIdentityMapExtension] Clear identity map for manager "manager-name"')
        ;

        $extension = new DoctrineClearIdentityMapExtension($registry);
        $extension->onPreReceived($context);
    }

    protected function createContext(): Context
    {
        $context = new Context($this->createMock(InteropContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setConsumer($this->createMock(Consumer::class));
        $context->setProcessor($this->createMock(Processor::class));

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createRegistryMock(): RegistryInterface
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected function createManagerMock(): ObjectManager
    {
        return $this->createMock(ObjectManager::class);
    }
}
