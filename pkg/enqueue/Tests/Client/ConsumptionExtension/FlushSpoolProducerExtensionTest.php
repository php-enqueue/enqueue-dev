<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\FlushSpoolProducerExtension;
use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

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

    public function testShouldFlushSpoolProducerOnEnd()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::once())
            ->method('flush')
        ;

        $end = new End($this->createInteropContextMock(), 1, 2, new NullLogger());

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onEnd($end);
    }

    public function testShouldFlushSpoolProducerOnPostReceived()
    {
        $producer = $this->createSpoolProducerMock();
        $producer
            ->expects(self::once())
            ->method('flush')
        ;

        $context = new PostMessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Message::class),
            'aResult',
            1,
            new NullLogger()
        );

        $extension = new FlushSpoolProducerExtension($producer);
        $extension->onPostMessageReceived($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createInteropContextMock(): Context
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
