<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\NoEffect\NullMessage;
use Enqueue\NoEffect\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Producer;
use Makasim\File\TempFile;

class FsProducerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, FsProducer::class);
    }

    public function testCouldBeConstructedWithContextAsFirstArgument()
    {
        new FsProducer($this->createContextMock());
    }

    public function testThrowIfDestinationNotFsOnSend()
    {
        $producer = new FsProducer($this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Fs\FsDestination but got Enqueue\NoEffect\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new FsMessage());
    }

    public function testThrowIfMessageNotFsOnSend()
    {
        $producer = new FsProducer($this->createContextMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Fs\FsMessage but it is Enqueue\NoEffect\NullMessage.');
        $producer->send(new FsDestination(TempFile::generate()), new NullMessage());
    }

    public function testShouldCallContextWorkWithFileAndCallbackToItOnSend()
    {
        $destination = new FsDestination(TempFile::generate());

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('workWithFile')
            ->with($this->identicalTo($destination), 'a+', $this->isInstanceOf(\Closure::class))
        ;

        $producer = new FsProducer($contextMock);

        $producer->send($destination, new FsMessage());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FsContext
     */
    private function createContextMock()
    {
        return $this->createMock(FsContext::class);
    }
}
