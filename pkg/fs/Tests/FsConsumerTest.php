<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConsumer;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Makasim\File\TempFile;

class FsConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, FsConsumer::class);
    }

    public function testShouldReturnDestinationSetInConstructorOnGetQueue()
    {
        $destination = new FsDestination(TempFile::generate());

        $consumer = new FsConsumer($this->createContextMock(), $destination, 1);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldAllowGetPreFetchCountSetInConstructor()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);

        $this->assertSame(123, $consumer->getPreFetchCount());
    }

    public function testShouldAllowGetPreviouslySetPreFetchCount()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);

        $consumer->setPreFetchCount(456);

        $this->assertSame(456, $consumer->getPreFetchCount());
    }

    public function testShouldDoNothingOnAcknowledge()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);
        $cloneWithInitialState = clone $consumer;
        $consumer->acknowledge(new FsMessage());
        $this->assertEquals($cloneWithInitialState, $consumer);
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);
        $cloneWithInitialState = clone $consumer;
        $consumer->reject(new FsMessage());
        $this->assertEquals($cloneWithInitialState, $consumer);
    }

    public function testCouldSetAndGetPollingInterval()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);
        $consumer->setPollingInterval(123456);

        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testShouldSendSameMessageToDestinationOnReQueue()
    {
        $message = new FsMessage();

        $destination = new FsDestination(TempFile::generate());

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($destination), $this->identicalTo($message))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $consumer = new FsConsumer($contextMock, $destination, 123);

        $consumer->reject($message, true);
    }

    public function testShouldCallContextWorkWithFileAndCallbackToItOnReceiveNoWait()
    {
        $destination = new FsDestination(TempFile::generate());

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('workWithFile')
            ->with($this->identicalTo($destination), 'c+', $this->isInstanceOf(\Closure::class))
        ;

        $consumer = new FsConsumer($contextMock, $destination, 1);

        $consumer->receiveNoWait();
    }

    public function testShouldWaitTwoSecondsForMessageAndExitOnReceive()
    {
        $destination = new FsDestination(TempFile::generate());

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->atLeastOnce())
            ->method('workWithFile')
        ;

        $consumer = new FsConsumer($contextMock, $destination, 1);

        $start = microtime(true);
        $consumer->receive(2000);
        $end = microtime(true);

        $this->assertGreaterThan(1.5, $end - $start);
        $this->assertLessThan(3.5, $end - $start);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FsProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(FsProducer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FsContext
     */
    private function createContextMock()
    {
        return $this->createMock(FsContext::class);
    }
}
