<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\ProducerInterface;
use Enqueue\Symfony\Client\ProduceCommand;
use Enqueue\Symfony\Client\SimpleProduceCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SimpleProduceCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfProduceCommand()
    {
        $this->assertClassExtends(ProduceCommand::class, SimpleProduceCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(SimpleProduceCommand::class);
    }

    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new SimpleProduceCommand($this->createProducerMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SimpleProduceCommand($this->createProducerMock());

        $this->assertEquals('enqueue:produce', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SimpleProduceCommand($this->createProducerMock());

        $options = $command->getDefinition()->getOptions();
        $this->assertCount(4, $options);
        $this->assertArrayHasKey('client', $options);
        $this->assertArrayHasKey('topic', $options);
        $this->assertArrayHasKey('command', $options);
        $this->assertArrayHasKey('header', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new SimpleProduceCommand($this->createProducerMock());

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

        $command = new SimpleProduceCommand($producerMock);

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either topic or command option should be set, none is set.');
        $tester->execute([
            'message' => 'theMessage',
        ]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
