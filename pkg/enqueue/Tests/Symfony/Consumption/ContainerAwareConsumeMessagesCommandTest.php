<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Psr\PsrQueue;
use Enqueue\Symfony\Consumption\ContainerAwareConsumeMessagesCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;

class ContainerAwareConsumeMessagesCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertArrayHasKey('processor-service', $arguments);
        $this->assertArrayHasKey('queue', $arguments);
    }

    public function testShouldThrowExceptionIfProcessorInstanceHasWrongClass()
    {
        $this->setExpectedException(\LogicException::class, 'Invalid message processor service given.'.
            ' It must be an instance of Enqueue\Psr\PsrProcessor but stdClass');

        $container = new Container();
        $container->set('processor-service', new \stdClass());

        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service',
        ]);
    }

    public function testShouldExecuteConsumption()
    {
        $processor = $this->createProcessor();

        $queue = $this->createQueueMock();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;
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
            ->expects($this->exactly(2))
            ->method('getPsrContext')
            ->will($this->returnValue($context))
        ;

        $container = new Container();
        $container->set('processor-service', $processor);

        $command = new ContainerAwareConsumeMessagesCommand($consumer);
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service',
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    protected function createContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrQueue
     */
    protected function createQueueMock()
    {
        return $this->createMock(PsrQueue::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
     */
    protected function createProcessor()
    {
        return $this->createMock(PsrProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumer
     */
    protected function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumer::class);
    }
}
