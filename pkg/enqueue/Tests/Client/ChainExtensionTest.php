<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverPreSend;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;
use Enqueue\Client\PostSend;
use Enqueue\Client\PreSend;
use Enqueue\Client\ProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;
use PHPUnit\Framework\TestCase;

class ChainExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(ChainExtension::class);
    }

    public function testCouldBeConstructedWithExtensionsArray()
    {
        new ChainExtension([$this->createExtension(), $this->createExtension()]);
    }

    public function testThrowIfArrayContainsNotExtension()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid extension given');

        new ChainExtension([$this->createExtension(), new \stdClass()]);
    }

    public function testShouldProxyOnPreSendEventToAllInternalExtensions()
    {
        $preSend = new PreSend(
            'aCommandOrTopic',
            new Message(),
            $this->createMock(ProducerInterface::class),
            $this->createMock(DriverInterface::class)
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreSendEvent')
            ->with($this->identicalTo($preSend))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreSendEvent')
            ->with($this->identicalTo($preSend))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPreSendEvent($preSend);
    }

    public function testShouldProxyOnPreSendCommandToAllInternalExtensions()
    {
        $preSend = new PreSend(
            'aCommandOrTopic',
            new Message(),
            $this->createMock(ProducerInterface::class),
            $this->createMock(DriverInterface::class)
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreSendCommand')
            ->with($this->identicalTo($preSend))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreSendCommand')
            ->with($this->identicalTo($preSend))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPreSendCommand($preSend);
    }

    public function testShouldProxyOnDriverPreSendToAllInternalExtensions()
    {
        $driverPreSend = new DriverPreSend(
            new Message(),
            $this->createMock(ProducerInterface::class),
            $this->createMock(DriverInterface::class)
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onDriverPreSend')
            ->with($this->identicalTo($driverPreSend))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onDriverPreSend')
            ->with($this->identicalTo($driverPreSend))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onDriverPreSend($driverPreSend);
    }

    public function testShouldProxyOnPostSentToAllInternalExtensions()
    {
        $postSend = new PostSend(
            new Message(),
            $this->createMock(ProducerInterface::class),
            $this->createMock(DriverInterface::class),
            $this->createMock(Destination::class),
            $this->createMock(TransportMessage::class)
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostSend')
            ->with($this->identicalTo($postSend))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostSend')
            ->with($this->identicalTo($postSend))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostSend($postSend);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }
}
