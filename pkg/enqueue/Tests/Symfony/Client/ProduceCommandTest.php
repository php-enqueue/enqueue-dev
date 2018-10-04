<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\ProducerInterface;
use Enqueue\Container\Container;
use Enqueue\Symfony\Client\ProduceCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ProduceCommandTest extends TestCase
{
    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new ProduceCommand($this->createMock(ContainerInterface::class));
    }

    public function testShouldHaveCommandName()
    {
        $command = new ProduceCommand($this->createMock(ContainerInterface::class));

        $this->assertEquals('enqueue:produce', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ProduceCommand($this->createMock(ContainerInterface::class));

        $options = $command->getDefinition()->getOptions();
        $this->assertCount(3, $options);
        $this->assertArrayHasKey('client', $options);
        $this->assertArrayHasKey('topic', $options);
        $this->assertArrayHasKey('command', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ProduceCommand($this->createMock(ContainerInterface::class));

        $arguments = $command->getDefinition()->getArguments();
        $this->assertCount(1, $arguments);

        $this->assertArrayHasKey('message', $arguments);
    }

    public function testThrowIfNeitherTopicNorCommandOptionsAreSet()
    {
        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $producerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $producerMock,
        ]));

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either topic or command option should be set, none is set.');
        $tester->execute([
            'message' => 'theMessage',
        ]);
    }

    public function testThrowIfBothTopicAndCommandOptionsAreSet()
    {
        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $producerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $producerMock,
        ]));

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either topic or command option should be set, both are set.');
        $tester->execute([
            'message' => 'theMessage',
            '--topic' => 'theTopic',
            '--command' => 'theCommand',
        ]);
    }

    public function testShouldSendEventToDefaultTransport()
    {
        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('sendEvent')
            ->with('theTopic', 'theMessage')
        ;
        $producerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $producerMock,
        ]));

        $tester = new CommandTester($command);
        $tester->execute([
            'message' => 'theMessage',
            '--topic' => 'theTopic',
        ]);
    }

    public function testShouldSendCommandToDefaultTransport()
    {
        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->with('theCommand', 'theMessage')
        ;
        $producerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $producerMock,
        ]));

        $tester = new CommandTester($command);
        $tester->execute([
            'message' => 'theMessage',
            '--command' => 'theCommand',
        ]);
    }

    public function testShouldSendEventToFooTransport()
    {
        $defaultProducerMock = $this->createProducerMock();
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $fooProducerMock = $this->createProducerMock();
        $fooProducerMock
            ->expects($this->once())
            ->method('sendEvent')
            ->with('theTopic', 'theMessage')
        ;
        $fooProducerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $defaultProducerMock,
            'enqueue.client.foo.producer' => $fooProducerMock,
        ]));

        $tester = new CommandTester($command);
        $tester->execute([
            'message' => 'theMessage',
            '--topic' => 'theTopic',
            '--client' => 'foo',
        ]);
    }

    public function testShouldSendCommandToFooTransport()
    {
        $defaultProducerMock = $this->createProducerMock();
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $fooProducerMock = $this->createProducerMock();
        $fooProducerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->with('theCommand', 'theMessage')
        ;
        $fooProducerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $defaultProducerMock,
            'enqueue.client.foo.producer' => $fooProducerMock,
        ]));

        $tester = new CommandTester($command);
        $tester->execute([
            'message' => 'theMessage',
            '--command' => 'theCommand',
            '--client' => 'foo',
        ]);
    }

    public function testThrowIfClientNotFound()
    {
        $defaultProducerMock = $this->createProducerMock();
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $defaultProducerMock
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $command = new ProduceCommand(new Container([
            'enqueue.client.default.producer' => $defaultProducerMock,
        ]));

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client "bar" is not supported.');
        $tester->execute([
            'message' => 'theMessage',
            '--command' => 'theCommand',
            '--client' => 'bar',
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
