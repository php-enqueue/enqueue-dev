<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Null\NullQueue;
use Enqueue\Psr\PsrContext;
use Enqueue\Symfony\Client\ConsumeMessagesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createQueueMetaRegistry([]),
            $this->createDriverMock()
        );
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createQueueMetaRegistry([]),
            $this->createDriverMock()
        );

        $this->assertEquals('enqueue:consume', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createQueueMetaRegistry([]),
            $this->createDriverMock()
        );

        $this->assertEquals(['enq:c'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createQueueMetaRegistry([]),
            $this->createDriverMock()
        );

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(4, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('setup-broker', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateProcessorMock(),
            $this->createQueueMetaRegistry([]),
            $this->createDriverMock()
        );

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('client-queue-names', $arguments);
    }

    public function testShouldExecuteConsumptionAndUseDefaultQueueName()
    {
        $queue = new NullQueue('');

        $processor = $this->createDelegateProcessorMock();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
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
        $consumer
            ->expects($this->once())
            ->method('getPsrContext')
            ->will($this->returnValue($context))
        ;

        $queueMetaRegistry = $this->createQueueMetaRegistry([
            'default' => [],
        ]);

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $queueMetaRegistry, $driver);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldExecuteConsumptionAndUseCustomClientDestinationName()
    {
        $queue = new NullQueue('');

        $processor = $this->createDelegateProcessorMock();

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
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
        $consumer
            ->expects($this->once())
            ->method('getPsrContext')
            ->will($this->returnValue($context))
        ;

        $queueMetaRegistry = $this->createQueueMetaRegistry([
            'non-default-queue' => [],
        ]);

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('non-default-queue')
            ->willReturn($queue)
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $queueMetaRegistry, $driver);

        $tester = new CommandTester($command);
        $tester->execute([
            'client-queue-names' => ['non-default-queue'],
        ]);
    }

    /**
     * @param array $destinationNames
     *
     * @return QueueMetaRegistry
     */
    private function createQueueMetaRegistry(array $destinationNames)
    {
        $config = new Config(
            'aPrefix',
            'anApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        return new QueueMetaRegistry($config, $destinationNames, 'default');
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
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumer
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
