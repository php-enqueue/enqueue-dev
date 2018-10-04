<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Container\Container;
use Enqueue\Symfony\Consumption\ConsumeCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeCommand($this->createMock(ContainerInterface::class));
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class));

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class));

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(8, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('idle-time', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
        $this->assertArrayHasKey('niceness', $options);
        $this->assertArrayHasKey('transport', $options);
        $this->assertArrayHasKey('logger', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class));

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

        $command = new ConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
        ]));

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

        $command = new ConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $defaultConsumer,
            'enqueue.transport.custom.queue_consumer' => $customConsumer,
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

        $command = new ConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $defaultConsumer,
        ]));

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport "not-defined" is not supported.');
        $tester->execute(['--transport' => 'not-defined']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
