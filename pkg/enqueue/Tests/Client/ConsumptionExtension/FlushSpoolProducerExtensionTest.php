<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\FlushSpoolProducerExtension;
use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class FlushSpoolProducerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, FlushSpoolProducerExtension::class);
    }

    public function testCouldBeConstructedWithSpoolProducerAsFirstArgument()
    {
        new FlushSpoolProducerExtension($this->createSpoolProducerMock());
    }

    public function testShouldDoNothingOnStart()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::never())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onStart($this->createContextMock());
    }

    public function testShouldDoNothingOnBeforeReceive()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::never())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onBeforeReceive($this->createContextMock());
    }

    public function testShouldDoNothingOnPreReceived()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::never())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onPreReceived($this->createContextMock());
    }

    public function testShouldDoNothingOnResult()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::never())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onResult($this->createContextMock());
    }

    public function testShouldDoNothingOnIdle()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::never())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onIdle($this->createContextMock());
    }

    public function testShouldFlushSpoolProducerOnInterrupted()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::once())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onInterrupted($this->createContextMock());
    }

    public function testShouldFlushSpoolProducerOnPostReceived()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::once())
            ->method('flush')
        ;

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onPostReceived($this->createContextMock());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SpoolProducer
     */
    private function createSpoolProducerMock()
    {
        return $this->createMock(SpoolProducer::class);
    }
}
