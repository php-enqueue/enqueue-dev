<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConsumer;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConsumer;
use Makasim\File\TempFile;

class FsConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, FsConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndDestinationAndPreFetchCountAsArguments()
    {
        new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 1);
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

        $consumer->acknowledge(new FsMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new FsConsumer($this->createContextMock(), new FsDestination(TempFile::generate()), 123);

        $consumer->reject(new FsMessage());
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
     * @return \PHPUnit_Framework_MockObject_MockObject|FsProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(FsProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FsContext
     */
    private function createContextMock()
    {
        return $this->createMock(FsContext::class);
    }
}
