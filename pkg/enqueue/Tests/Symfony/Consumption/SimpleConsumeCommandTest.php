<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Symfony\Consumption\ConsumeCommand;
use Enqueue\Symfony\Consumption\SimpleConsumeCommand;
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
        new SimpleConsumeCommand($this->createQueueConsumerMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock());

        $this->assertEquals('enqueue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock());

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
        $command = new SimpleConsumeCommand($this->createQueueConsumerMock());

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

        $command = new SimpleConsumeCommand($consumer);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|QueueConsumerInterface
     */
    private function createQueueConsumerMock()
    {
        return $this->createMock(QueueConsumerInterface::class);
    }
}
