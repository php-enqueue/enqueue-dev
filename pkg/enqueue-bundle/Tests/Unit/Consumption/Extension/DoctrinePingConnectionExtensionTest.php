<?php

namespace Enqueue\Bundle\Tests\Unit\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Enqueue\Bundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Enqueue\Consumption\Context;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Processor;
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
            ->method('isConnected')
            ->will($this->returnValue(true))
        ;
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

        $context = $this->createContext();
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
            ->method('isConnected')
            ->will($this->returnValue(true))
        ;
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

        $context = $this->createContext();
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

    public function testShouldSkipIfConnectionWasNotOpened()
    {
        $connection1 = $this->createConnectionMock();
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false))
        ;
        $connection1
            ->expects($this->never())
            ->method('ping')
        ;

        // 2nd connection was opened in the past
        $connection2 = $this->createConnectionMock();
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(true))
        ;
        $connection2
            ->expects($this->once())
            ->method('ping')
            ->will($this->returnValue(true))
        ;

        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->never())
            ->method('debug')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnections')
            ->will($this->returnValue([$connection1, $connection2]))
        ;

        $extension = new DoctrinePingConnectionExtension($registry);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected function createConnectionMock(): Connection
    {
        return $this->createMock(Connection::class);
    }
}
