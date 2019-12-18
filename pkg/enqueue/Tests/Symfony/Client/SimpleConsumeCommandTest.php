<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Null\NullQueue;
use Enqueue\Symfony\Client\ConsumeCommand;
use Enqueue\Symfony\Client\SimpleConsumeCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SimpleConsumeCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfConsumeCommand()
    {
        $this->assertClassExtends(ConsumeCommand::class, SimpleConsumeCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(SimpleConsumeCommand::class);
    }

    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new SimpleConsumeCommand($this->createQueueConsumerMock(), $this->createDriverStub(), $this->createDelegateProcessorMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock(), $this->createDriverStub(), $this->createDelegateProcessorMock());

        $this->assertEquals('enqueue:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock(), $this->createDriverStub(), $this->createDelegateProcessorMock());

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
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock(), $this->createDriverStub(), $this->createDelegateProcessorMock());

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

        $command = new SimpleConsumeCommand($consumer, $driver, $processor);

        $tester = new CommandTester($command);
        $tester->execute([]);
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
