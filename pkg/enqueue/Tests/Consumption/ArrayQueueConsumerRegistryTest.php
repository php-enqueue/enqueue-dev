<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\ArrayQueueConsumerRegistry;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ArrayQueueConsumerRegistryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueConsumerRegistryInterface()
    {
        $this->assertClassImplements(QueueConsumerRegistryInterface::class, ArrayQueueConsumerRegistry::class);
    }

    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new ArrayQueueConsumerRegistry();
    }

    public function testShouldThrowExceptionIfQueueConsumerIsNotSet()
    {
        $registry = new ArrayQueueConsumerRegistry();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('QueueConsumer was not found, name: "queueConsumer-name".');
        $registry->get('queueConsumer-name');
    }

    public function testShouldAllowGetQueueConsumerAddedViaConstructor()
    {
        $queueConsumer = $this->createQueueConsumerMock();

        $registry = new ArrayQueueConsumerRegistry(['aFooName' => $queueConsumer]);

        $this->assertSame($queueConsumer, $registry->get('aFooName'));
    }

    public function testShouldAllowGetQueueConsumerAddedViaAddMethod()
    {
        $queueConsumer = $this->createQueueConsumerMock();

        $registry = new ArrayQueueConsumerRegistry();
        $registry->add('aFooName', $queueConsumer);

        $this->assertSame($queueConsumer, $registry->get('aFooName'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createQueueConsumerMock(): QueueConsumerInterface
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
