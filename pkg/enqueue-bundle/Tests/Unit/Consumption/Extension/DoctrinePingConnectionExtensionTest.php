<?php

namespace Enqueue\Bundle\Tests\Unit\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Enqueue\Bundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Enqueue\Consumption\Context;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrinePingConnectionExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new DoctrinePingConnectionExtension($this->createRegistryMock());
    }

    public function testShouldNotReconnectIfConnectionIsOK()
    {
        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('ping')
            ->will($this->returnValue(true))
        ;
        $connection
            ->expects($this->never())
            ->method('close')
        ;
        $connection
            ->expects($this->never())
            ->method('connect')
        ;

        $context = $this->createPsrContext();
        $context->getLogger()
            ->expects($this->never())
            ->method('debug')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([$connection]))
        ;

        $extension = new DoctrinePingConnectionExtension($registry);
        $extension->onPreReceived($context);
    }

    public function testShouldDoesReconnectIfConnectionFailed()
    {
        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('ping')
            ->will($this->returnValue(false))
        ;
        $connection
            ->expects($this->once())
            ->method('close')
        ;
        $connection
            ->expects($this->once())
            ->method('connect')
        ;

        $context = $this->createPsrContext();
        $context->getLogger()
            ->expects($this->at(0))
            ->method('debug')
            ->with('[DoctrinePingConnectionExtension] Connection is not active trying to reconnect.')
        ;
        $context->getLogger()
            ->expects($this->at(1))
            ->method('debug')
            ->with('[DoctrinePingConnectionExtension] Connection is active now.')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([$connection]))
        ;

        $extension = new DoctrinePingConnectionExtension($registry);
        $extension->onPreReceived($context);
    }

    /**
     * @return Context
     */
    protected function createPsrContext()
    {
        $context = new Context($this->createMock(PsrContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setPsrConsumer($this->createMock(PsrConsumer::class));
        $context->setPsrProcessor($this->createMock(PsrProcessor::class));

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createRegistryMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}
