<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrProducer;
use Makasim\File\TempFile;

class FsProducerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, FsProducer::class);
    }

    public function testCouldBeConstructedWithContextAsFirstArgument()
    {
        new FsProducer($this->createContextMock());
    }

    public function testThrowIfDestinationNotFsOnSend()
    {
        $producer = new FsProducer($this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Fs\FsDestination but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new FsMessage());
    }

    public function testThrowIfMessageNotFsOnSend()
    {
        $producer = new FsProducer($this->createContextMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Fs\FsMessage but it is Enqueue\Null\NullMessage.');
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
     * @return \PHPUnit_Framework_MockObject_MockObject|FsContext
     */
    private function createContextMock()
    {
        return $this->createMock(FsContext::class);
    }
}
