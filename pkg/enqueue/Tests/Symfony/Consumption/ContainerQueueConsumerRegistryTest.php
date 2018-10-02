<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Enqueue\Symfony\Consumption\ContainerQueueConsumerRegistry;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerQueueConsumerRegistryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueConsumerRegistryInterface()
    {
        $this->assertClassImplements(QueueConsumerRegistryInterface::class, ContainerQueueConsumerRegistry::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(ContainerQueueConsumerRegistry::class);
    }

    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new ContainerQueueConsumerRegistry($this->createContainerMock());
    }

    public function testShouldAllowGetQueueConsumer()
    {
        $queueConsumerMock = $this->createQueueConsumerMock();

        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('queueConsumer-name')
            ->willReturn(true)
        ;
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('queueConsumer-name')
            ->willReturn($queueConsumerMock)
        ;

        $registry = new ContainerQueueConsumerRegistry($containerMock);
        $this->assertSame($queueConsumerMock, $registry->get('queueConsumer-name'));
    }

    public function testThrowErrorIfServiceDoesNotImplementQueueConsumerReturnType()
    {
        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('queueConsumer-name')
            ->willReturn(true)
        ;
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('queueConsumer-name')
            ->willReturn(new \stdClass())
        ;

        $registry = new ContainerQueueConsumerRegistry($containerMock);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Return value of Enqueue\Symfony\Consumption\ContainerQueueConsumerRegistry::get() must implement interface Enqueue\Consumption\QueueConsumerInterface, instance of stdClass returned');
        $registry->get('queueConsumer-name');
    }

    public function testShouldThrowExceptionIfQueueConsumerIsNotSet()
    {
        $containerMock = $this->createContainerMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('queueConsumer-name')
            ->willReturn(false)
        ;

        $registry = new ContainerQueueConsumerRegistry($containerMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service locator does not have a queue consumer with name "queueConsumer-name".');
        $registry->get('queueConsumer-name');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createQueueConsumerMock(): QueueConsumerInterface
    {
        return $this->createMock(QueueConsumerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createContainerMock(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }
}
