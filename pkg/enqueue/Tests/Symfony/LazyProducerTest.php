<?php

declare(strict_types=1);

namespace Enqueue\Tests\Symfony;

use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\Promise;
use Enqueue\Symfony\Client\LazyProducer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LazyProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(ProducerInterface::class, LazyProducer::class);
    }

    public function testCouldBeConstructedWithContainerAndServiceId()
    {
        new LazyProducer($this->createContainerMock(), 'realProducerId');
    }

    public function testShouldNotCallRealProducerInConstructor()
    {
        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->never())
            ->method('get')
        ;

        new LazyProducer($containerMock, 'realProducerId');
    }

    public function testShouldProxyAllArgumentOnSendEvent()
    {
        $topic = 'theTopic';
        $message = 'theMessage';

        $realProducerMock = $this->createProducerMock();
        $realProducerMock
            ->expects($this->once())
            ->method('sendEvent')
            ->with($topic, $message)
        ;

        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('realProducerId')
            ->willReturn($realProducerMock)
        ;

        $lazyProducer = new LazyProducer($containerMock, 'realProducerId');

        $lazyProducer->sendEvent($topic, $message);
    }

    public function testShouldProxyAllArgumentOnSendCommand()
    {
        $command = 'theCommand';
        $message = 'theMessage';
        $needReply = false;

        $realProducerMock = $this->createProducerMock();
        $realProducerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->with($command, $message, $needReply)
        ;

        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('realProducerId')
            ->willReturn($realProducerMock)
        ;

        $lazyProducer = new LazyProducer($containerMock, 'realProducerId');

        $result = $lazyProducer->sendCommand($command, $message, $needReply);

        $this->assertNull($result);
    }

    public function testShouldProxyReturnedPromiseBackOnSendCommand()
    {
        $expectedPromise = $this->createMock(Promise::class);

        $realProducerMock = $this->createProducerMock();
        $realProducerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->willReturn($expectedPromise)
        ;

        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('realProducerId')
            ->willReturn($realProducerMock)
        ;

        $lazyProducer = new LazyProducer($containerMock, 'realProducerId');

        $actualPromise = $lazyProducer->sendCommand('aCommand', 'aMessage', true);

        $this->assertSame($expectedPromise, $actualPromise);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProducerInterface
     */
    private function createProducerMock(): ProducerInterface
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    private function createContainerMock(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }
}
