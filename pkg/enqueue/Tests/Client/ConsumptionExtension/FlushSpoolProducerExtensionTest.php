<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\FlushSpoolProducerExtension;
use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Message;
use Interop\Queue\SubscriptionConsumer;
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
    private function createInteropContextMock(): \Interop\Queue\Context
    {
        return $this->createMock(\Interop\Queue\Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
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
