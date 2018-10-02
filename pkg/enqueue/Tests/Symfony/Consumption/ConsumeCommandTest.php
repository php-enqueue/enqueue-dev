<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ArrayQueueConsumerRegistry;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Enqueue\Symfony\Consumption\ConsumeCommand;
use Interop\Queue\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeCommand($this->createMock(QueueConsumerRegistryInterface::class));
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeCommand($this->createMock(QueueConsumerRegistryInterface::class));

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeCommand($this->createMock(QueueConsumerRegistryInterface::class));

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(7, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('idle-timeout', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
        $this->assertArrayHasKey('niceness', $options);
        $this->assertArrayHasKey('transport', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeCommand($this->createMock(QueueConsumerRegistryInterface::class));

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(0, $arguments);
    }

    public function testShouldExecuteDefaultConsumption()
    {
        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new ArrayQueueConsumerRegistry(['default' => $consumer]));

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldExecuteCustomConsumption()
    {
        $defaultConsumer = $this->createQueueConsumerMock();
        $defaultConsumer
            ->expects($this->never())
            ->method('consume')
        ;

        $customConsumer = $this->createQueueConsumerMock();
        $customConsumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new ArrayQueueConsumerRegistry([
            'default' => $defaultConsumer,
            'custom' => $customConsumer,
        ]));

        $tester = new CommandTester($command);
        $tester->execute(['--transport' => 'custom']);
    }

    public function testThrowIfNotDefinedTransportRequested()
    {
        $defaultConsumer = $this->createQueueConsumerMock();
        $defaultConsumer
            ->expects($this->never())
            ->method('consume')
        ;

        $command = new ConsumeCommand(new ArrayQueueConsumerRegistry([
            'default' => $defaultConsumer,
        ]));

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('QueueConsumer was not found, name: "not-defined".');
        $tester->execute(['--transport' => 'not-defined']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
