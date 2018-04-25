<?php

namespace Enqueue\Tests\Symfony\Client\Meta;

use Enqueue\Client\Meta\QueueMeta;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class QueuesCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, QueuesCommand::class);
    }

    public function testCouldBeConstructedWithQueueMetaRegistryAsFirstArgument()
    {
        new QueuesCommand($this->createQueueMetaRegistryStub());
    }

    public function testShouldHaveCommandName()
    {
        $command = new QueuesCommand($this->createQueueMetaRegistryStub());

        $this->assertEquals('enqueue:queues', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new QueuesCommand($this->createQueueMetaRegistryStub());

        $this->assertEquals(['enq:m:q', 'debug:enqueue:queues'], $command->getAliases());
    }

    public function testShouldShowMessageFoundZeroDestinationsIfAnythingInRegistry()
    {
        $command = new QueuesCommand($this->createQueueMetaRegistryStub());

        $output = $this->executeCommand($command);

        $this->assertContains('Found 0 destinations', $output);
    }

    public function testShouldShowMessageFoundTwoDestinations()
    {
        $command = new QueuesCommand($this->createQueueMetaRegistryStub([
            new QueueMeta('aClientName', 'aDestinationName'),
            new QueueMeta('anotherClientName', 'anotherDestinationName'),
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 2 destinations', $output);
    }

    public function testShouldShowInfoAboutDestinations()
    {
        $command = new QueuesCommand($this->createQueueMetaRegistryStub([
            new QueueMeta('aFooClientName', 'aFooDestinationName', ['fooSubscriber']),
            new QueueMeta('aBarClientName', 'aBarDestinationName', ['barSubscriber']),
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('aFooClientName', $output);
        $this->assertContains('aFooDestinationName', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('aBarClientName', $output);
        $this->assertContains('aBarDestinationName', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param Command  $command
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(Command $command, array $arguments = [])
    {
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }

    /**
     * @param mixed $destinations
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueMetaRegistry
     */
    protected function createQueueMetaRegistryStub($destinations = [])
    {
        $registryMock = $this->createMock(QueueMetaRegistry::class);
        $registryMock
            ->expects($this->any())
            ->method('getQueuesMeta')
            ->willReturn($destinations)
        ;

        return $registryMock;
    }
}
