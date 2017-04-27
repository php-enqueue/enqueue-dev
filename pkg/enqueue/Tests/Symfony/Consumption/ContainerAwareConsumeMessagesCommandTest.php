<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Psr\PsrQueue;
use Enqueue\Symfony\Consumption\ContainerAwareConsumeMessagesCommand;
use Enqueue\Tests\Symfony\Consumption\Mock\QueueSubscriberProcessor;
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

        $this->assertCount(4, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('queue', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('processor-service', $arguments);
    }

    public function testShouldThrowExceptionIfProcessorInstanceHasWrongClass()
    {
        $container = new Container();
        $container->set('processor-service', new \stdClass());

        $command = new ContainerAwareConsumeMessagesCommand($this->createQueueConsumerMock());
        $command->setContainer($container);

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid message processor service given. It must be an instance of Enqueue\Psr\PsrProcessor but stdClass');
        $tester->execute([
            'processor-service' => 'processor-service',
            '--queue' => ['queue-name'],
        ]);
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

        $container = new Container();
        $container->set('processor-service', $processor);

        $command = new ContainerAwareConsumeMessagesCommand($consumer);
        $command->setContainer($container);

        $tester = new CommandTester($command);


        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queues are not provided. The processor must implement "Enqueue\Consumption\QueueSubscriberInterface" interface and it must return not empty array of queues or queues set using --queue option.');
        $tester->execute([
            'processor-service' => 'processor-service',
        ]);
    }

    public function testShouldExecuteConsumptionWithExplisitlySetQueueViaQueueOption()
    {
        $processor = $this->createProcessor();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('close')
        ;

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
        $consumer
            ->expects($this->exactly(1))
            ->method('getPsrContext')
            ->will($this->returnValue($context))
        ;

        $container = new Container();
        $container->set('processor-service', $processor);

        $command = new ContainerAwareConsumeMessagesCommand($consumer);
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'processor-service' => 'processor-service',
            '--queue' => ['queue-name'],
        ]);
    }

    public function testShouldExecuteConsumptionWhenProcessorImplementsQueueSubscriberInterface()
    {
        $processor = new QueueSubscriberProcessor();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('close')
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->at(0))
            ->method('bind')
            ->with('fooSubscribedQueues', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(1))
            ->method('bind')
            ->with('barSubscribedQueues', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->at(2))
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class))
        ;
        $consumer
            ->expects($this->at(3))
            ->method('getPsrContext')
            ->will($this->returnValue($context))
        ;

        $container = new Container();
        $container->set('processor-service', $processor);

        $command = new ContainerAwareConsumeMessagesCommand($consumer);
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'processor-service' => 'processor-service'
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
