<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Container\Container;
use Enqueue\Null\NullQueue;
use Enqueue\Symfony\Client\ConsumeCommand;
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

        $this->assertEquals('enqueue:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(9, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
        $this->assertArrayHasKey('niceness', $options);
        $this->assertArrayHasKey('client', $options);
        $this->assertArrayHasKey('logger', $options);
        $this->assertArrayHasKey('skip', $options);
        $this->assertArrayHasKey('setup-broker', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeCommand($this->createMock(ContainerInterface::class), 'default');

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('client-queue-names', $arguments);
    }

    public function testShouldBindDefaultQueueOnly()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([]);

        $processor = $this->createDelegateProcessorMock();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with($this->identicalTo($queue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($queue)
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldUseRequestedClient()
    {
        $defaultProcessor = $this->createDelegateProcessorMock();

        $defaultConsumer = $this->createQueueConsumerMock();
        $defaultConsumer
            ->expects($this->never())
            ->method('bind')
        ;
        $defaultConsumer
            ->expects($this->never())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $defaultDriver = $this->createDriverStub(new RouteCollection([]));
        $defaultDriver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([]);

        $fooProcessor = $this->createDelegateProcessorMock();

        $fooConsumer = $this->createQueueConsumerMock();
        $fooConsumer
            ->expects($this->once())
            ->method('bind')
            ->with($this->identicalTo($queue), $this->identicalTo($fooProcessor))
        ;
        $fooConsumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $fooDriver = $this->createDriverStub($routeCollection);
        $fooDriver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($queue)
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $defaultConsumer,
            'enqueue.client.default.driver' => $defaultDriver,
            'enqueue.client.default.delegate_processor' => $defaultProcessor,
            'enqueue.client.foo.queue_consumer' => $fooConsumer,
            'enqueue.client.foo.driver' => $fooDriver,
            'enqueue.client.foo.delegate_processor' => $fooProcessor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            '--client' => 'foo',
        ]);
    }

    public function testThrowIfNotDefinedClientRequested()
    {
        $routeCollection = new RouteCollection([]);

        $processor = $this->createDelegateProcessorMock();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->never())
            ->method('bind')
        ;
        $consumer
            ->expects($this->never())
            ->method('consume')
        ;

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client "not-defined" is not supported.');
        $tester->execute([
            '--client' => 'not-defined',
        ]);
    }

    public function testShouldBindDefaultQueueIfRouteUseDifferentQueue()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor'),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with($this->identicalTo($queue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($queue)
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldBindCustomExecuteConsumptionAndUseCustomClientDestinationName()
    {
        $defaultQueue = new NullQueue('');
        $customQueue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor', ['queue' => 'custom']),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->at(3))
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($defaultQueue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue')
            ->with('custom', true)
            ->willReturn($customQueue)
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->at(0))
            ->method('bind')
            ->with($this->identicalTo($defaultQueue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(1))
            ->method('bind')
            ->with($this->identicalTo($customQueue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(2))
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldBindUserProvidedQueues()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor', ['queue' => 'custom']),
            new Route('topic', Route::TOPIC, 'processor', ['queue' => 'non-default-queue']),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('non-default-queue', true)
            ->willReturn($queue)
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with($this->identicalTo($queue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'client-queue-names' => ['non-default-queue'],
        ]);
    }

    public function testShouldBindNotPrefixedQueue()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor', ['queue' => 'non-prefixed-queue', 'prefix_queue' => false]),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('non-prefixed-queue', false)
            ->willReturn($queue)
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with($this->identicalTo($queue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            'client-queue-names' => ['non-prefixed-queue'],
        ]);
    }

    public function testShouldBindQueuesOnlyOnce()
    {
        $defaultQueue = new NullQueue('');
        $customQueue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('fooTopic', Route::TOPIC, 'processor', ['queue' => 'custom']),
            new Route('barTopic', Route::TOPIC, 'processor', ['queue' => 'custom']),
            new Route('ololoTopic', Route::TOPIC, 'processor', []),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->at(3))
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($defaultQueue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue', true)
            ->with('custom')
            ->willReturn($customQueue)
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->at(0))
            ->method('bind')
            ->with($this->identicalTo($defaultQueue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(1))
            ->method('bind')
            ->with($this->identicalTo($customQueue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(2))
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldNotBindExternalRoutes()
    {
        $defaultQueue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('barTopic', Route::TOPIC, 'processor', ['queue' => null]),
            new Route('fooTopic', Route::TOPIC, 'processor', ['queue' => 'external_queue', 'external' => true]),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->exactly(1))
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($defaultQueue)
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->exactly(1))
            ->method('bind')
            ->with($this->identicalTo($defaultQueue), $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(1))
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldSkipQueueConsumptionAndUseCustomClientDestinationName()
    {
        $queue = new NullQueue('');

        $processor = $this->createDelegateProcessorMock();

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->exactly(3))
            ->method('bind')
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;

        $routeCollection = new RouteCollection([
            new Route('fooTopic', Route::TOPIC, 'processor', ['queue' => 'fooQueue']),
            new Route('barTopic', Route::TOPIC, 'processor', ['queue' => 'barQueue']),
            new Route('ololoTopic', Route::TOPIC, 'processor', ['queue' => 'ololoQueue']),
        ]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->at(3))
            ->method('createQueue', true)
            ->with('default')
            ->willReturn($queue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue', true)
            ->with('fooQueue')
            ->willReturn($queue)
        ;
        $driver
            ->expects($this->at(5))
            ->method('createQueue', true)
            ->with('ololoQueue')
            ->willReturn($queue)
        ;

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            '--skip' => ['barQueue'],
        ]);
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

        $defaultQueue = new NullQueue('default');

        $routeCollection = new RouteCollection([]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->exactly(1))
            ->method('createQueue')
            ->with('default', true)
            ->willReturn($defaultQueue)
        ;

        $consumer = new QueueConsumer($this->createContextStub(), $stubExtension);

        $command = new ConsumeCommand(new Container([
            'enqueue.client.default.queue_consumer' => $consumer,
            'enqueue.client.default.driver' => $driver,
            'enqueue.client.default.delegate_processor' => $processor,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals($testExitCode, $tester->getStatusCode());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DelegateProcessor
     */
    private function createDelegateProcessorMock()
    {
        return $this->createMock(DelegateProcessor::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DriverInterface
     */
    private function createDriverStub(?RouteCollection $routeCollection = null): DriverInterface
    {
        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection ?? new RouteCollection([]))
        ;

        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn(Config::create('aPrefix', 'anApp'))
        ;

        return $driverMock;
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
