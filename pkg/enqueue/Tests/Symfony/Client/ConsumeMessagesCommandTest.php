<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Null\NullQueue;
use Enqueue\Symfony\Client\ConsumeMessagesCommand;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createDriverStub()
        );
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createDriverStub()
        );

        $this->assertEquals('enqueue:consume', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createDriverStub()
        );

        $this->assertEquals(['enq:c'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createDriverStub()
        );

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(8, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('setup-broker', $options);
        $this->assertArrayHasKey('idle-timeout', $options);
        $this->assertArrayHasKey('receive-timeout', $options);
        $this->assertArrayHasKey('skip', $options);
        $this->assertArrayHasKey('niceness', $options);
    }

    public function testShouldHaveExpectedArguments()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createDriverStub()
        );

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('client-queue-names', $arguments);
    }

    public function testShouldBindDefaultQueueOnly()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([]);

        $processor = $this->createDelegateProcessorMock();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->never())
            ->method('close')
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

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldBindDefaultQueueIfRouteDoesNotDefineQueue()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor'),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->never())
            ->method('close')
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

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

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
            ->with('default')
            ->willReturn($defaultQueue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue')
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

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldBindUserProvidedQueues()
    {
        $queue = new NullQueue('');

        $routeCollection = new RouteCollection([
            new Route('topic', Route::TOPIC, 'processor', ['queue' => 'custom']),
        ]);

        $processor = $this->createDelegateProcessorMock();

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('non-default-queue')
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

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

        $tester = new CommandTester($command);
        $tester->execute([
            'client-queue-names' => ['non-default-queue'],
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
            ->with('default')
            ->willReturn($defaultQueue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue')
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

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

        $tester = new CommandTester($command);
        $tester->execute([
//            'client-queue-names' => ['non-default-queue'],
        ]);
    }

    public function testShouldSkipQueueConsumptionAndUseCustomClientDestinationName()
    {
        $queue = new NullQueue('');

        $processor = $this->createDelegateProcessorMock();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->never())
            ->method('close')
        ;

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
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;
        $driver
            ->expects($this->at(4))
            ->method('createQueue')
            ->with('fooQueue')
            ->willReturn($queue)
        ;
        $driver
            ->expects($this->at(5))
            ->method('createQueue')
            ->with('ololoQueue')
            ->willReturn($queue)
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $driver);

        $tester = new CommandTester($command);
        $tester->execute([
            '--skip' => ['barQueue'],
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DelegateProcessor
     */
    private function createDelegateProcessorMock()
    {
        return $this->createMock(DelegateProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverStub(RouteCollection $routeCollection = null): DriverInterface
    {
        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection)
        ;

        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn(Config::create('aPrefix', 'anApp'))
        ;

        return $driverMock;
    }
}
