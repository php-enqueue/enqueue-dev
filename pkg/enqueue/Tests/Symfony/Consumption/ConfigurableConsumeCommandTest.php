<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\ArrayProcessorRegistry;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Container\Container;
use Enqueue\Symfony\Consumption\ConfigurableConsumeCommand;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;
use Interop\Queue\Queue as InteropQueue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigurableConsumeCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, ConfigurableConsumeCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(ConfigurableConsumeCommand::class);
    }

    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConfigurableConsumeCommand($this->createMock(ContainerInterface::class), 'default');
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConfigurableConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConfigurableConsumeCommand($this->createMock(ContainerInterface::class), 'default');

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
        $command = new ConfigurableConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertArrayHasKey('processor', $arguments);
        $this->assertArrayHasKey('queues', $arguments);
    }

    public function testThrowIfNeitherQueueOptionNorProcessorImplementsQueueSubscriberInterface()
    {
        $processor = $this->createProcessor();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->never())
            ->method('bind')
        ;
        $consumer
            ->expects($this->never())
            ->method('consume')
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
            'enqueue.transport.default.processor_registry' => new ArrayProcessorRegistry(['aProcessor' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue is not provided. The processor must implement "Enqueue\Consumption\QueueSubscriberInterface" interface and it must return not empty array of queues or a queue set using as a second argument.');
        $tester->execute([
            'processor' => 'aProcessor',
        ]);
    }

    public function testShouldExecuteConsumptionWithExplicitlySetQueue()
    {
        $processor = $this->createProcessor();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with('queue-name', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
            'enqueue.transport.default.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'processor' => 'processor-service',
            'queues' => ['queue-name'],
        ]);
    }

    public function testThrowIfTransportNotDefined()
    {
        $processor = $this->createProcessor();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->never())
            ->method('bind')
        ;
        $consumer
            ->expects($this->never())
            ->method('consume')
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
            'enqueue.transport.default.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport "not-defined" is not supported.');
        $tester->execute([
            'processor' => 'processor-service',
            'queues' => ['queue-name'],
            '--transport' => 'not-defined',
        ]);
    }

    public function testShouldExecuteConsumptionWithSeveralCustomQueues()
    {
        $processor = $this->createProcessor();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects(self::once())
            ->method('bind')
            ->with('queue-name', $this->identicalTo($processor))
        ;
        $consumer
            ->expects(self::once())
            ->method('bind')
            ->with('another-queue-name', $this->identicalTo($processor))
        ;
        $consumer
            ->expects(self::once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
            'enqueue.transport.default.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'processor' => 'processor-service',
            'queues' => ['queue-name', 'another-queue-name'],
        ]);
    }

    public function testShouldExecuteConsumptionWhenProcessorImplementsQueueSubscriberInterface()
    {
        $processor = new class() implements Processor, QueueSubscriberInterface {
            public function process(InteropMessage $message, Context $context)
            {
            }

            public static function getSubscribedQueues()
            {
                return ['fooSubscribedQueues', 'barSubscribedQueues'];
            }
        };

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects(self::once())
            ->method('bind')
            ->with('fooSubscribedQueues', $this->identicalTo($processor))
        ;
        $consumer
            ->expects(self::once())
            ->method('bind')
            ->with('barSubscribedQueues', $this->identicalTo($processor))
        ;
        $consumer
            ->expects(self::once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.default.queue_consumer' => $consumer,
            'enqueue.transport.default.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'processor' => 'processor-service',
        ]);
    }

    public function testShouldExecuteConsumptionWithCustomTransportExplicitlySetQueue()
    {
        $processor = $this->createProcessor();

        $fooConsumer = $this->createQueueConsumerMock();
        $fooConsumer
            ->expects($this->never())
            ->method('bind')
        ;
        $fooConsumer
            ->expects($this->never())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $barConsumer = $this->createQueueConsumerMock();
        $barConsumer
            ->expects($this->once())
            ->method('bind')
            ->with('queue-name', $this->identicalTo($processor))
        ;
        $barConsumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConfigurableConsumeCommand(new Container([
            'enqueue.transport.foo.queue_consumer' => $fooConsumer,
            'enqueue.transport.foo.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
            'enqueue.transport.bar.queue_consumer' => $barConsumer,
            'enqueue.transport.bar.processor_registry' => new ArrayProcessorRegistry(['processor-service' => $processor]),
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'processor' => 'processor-service',
            'queues' => ['queue-name'],
            '--transport' => 'bar',
        ]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|InteropQueue
     */
    protected function createQueueMock()
    {
        return $this->createMock(InteropQueue::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Processor
     */
    protected function createProcessor()
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|QueueConsumerInterface
     */
    protected function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
