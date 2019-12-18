<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Container\Container;
use Enqueue\Null\NullQueue;
use Enqueue\Symfony\Consumption\ConsumeCommand;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, ConsumeCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(ConsumeCommand::class);
    }

    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(7, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
        $this->assertArrayHasKey('niceness', $options);
        $this->assertArrayHasKey('transport', $options);
        $this->assertArrayHasKey('logger', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');

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
        ]), 'default');

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
        ]), 'default');

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
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport "not-defined" is not supported.');
        $tester->execute(['--transport' => 'not-defined']);
    }

    public function testShouldReturnExitStatusIfSet()
    {
        $testExitCode = 678;

        $stubExtension = $this->createExtension();

        $stubExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($testExitCode) {
                $context->interruptExecution($testExitCode);
            })
        ;

        $consumer = new QueueConsumer($this->createContextStub(), $stubExtension);

        $command = new ConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertEquals($testExitCode, $tester->getStatusCode());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createContextWithoutSubscriptionConsumerMock(): InteropContext
    {
        $contextMock = $this->createMock(InteropContext::class);
        $contextMock
            ->expects($this->any())
            ->method('createSubscriptionConsumer')
            ->willThrowException(SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt())
        ;

        return $contextMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|InteropContext
     */
    private function createContextStub(Consumer $consumer = null): InteropContext
    {
        $context = $this->createContextWithoutSubscriptionConsumerMock();
        $context
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $queueName) {
                return new NullQueue($queueName);
            })
        ;
        $context
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturnCallback(function (Queue $queue) use ($consumer) {
                return $consumer ?: $this->createConsumerStub($queue);
            })
        ;

        return $context;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExtensionInterface
     */
    private function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @param mixed|null $queue
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Consumer
     */
    private function createConsumerStub($queue = null): Consumer
    {
        if (is_string($queue)) {
            $queue = new NullQueue($queue);
        }

        $consumerMock = $this->createMock(Consumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $consumerMock;
    }
}
