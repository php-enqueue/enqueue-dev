<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Container\Container;
use Enqueue\Symfony\Client\SetupBrokerCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SetupBrokerCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, SetupBrokerCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(SetupBrokerCommand::class);
    }

    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new SetupBrokerCommand($this->createMock(ContainerInterface::class), 'default');
    }

    public function testShouldHaveCommandName()
    {
        $command = new SetupBrokerCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals('enqueue:setup-broker', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new SetupBrokerCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals(['enq:sb'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SetupBrokerCommand($this->createMock(ContainerInterface::class), 'default');

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(1, $options);
        $this->assertArrayHasKey('client', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new SetupBrokerCommand($this->createMock(ContainerInterface::class), 'default');

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(0, $arguments);
    }

    public function testShouldCallDriverSetupBrokerMethod()
    {
        $driver = $this->createClientDriverMock();
        $driver
            ->expects($this->once())
            ->method('setupBroker')
        ;

        $command = new SetupBrokerCommand(new Container([
            'enqueue.client.default.driver' => $driver,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertContains('Broker set up', $tester->getDisplay());
    }

    public function testShouldCallRequestedClientDriverSetupBrokerMethod()
    {
        $defaultDriver = $this->createClientDriverMock();
        $defaultDriver
            ->expects($this->never())
            ->method('setupBroker')
        ;

        $fooDriver = $this->createClientDriverMock();
        $fooDriver
            ->expects($this->once())
            ->method('setupBroker')
        ;

        $command = new SetupBrokerCommand(new Container([
            'enqueue.client.default.driver' => $defaultDriver,
            'enqueue.client.foo.driver' => $fooDriver,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            '--client' => 'foo',
        ]);

        $this->assertContains('Broker set up', $tester->getDisplay());
    }

    public function testShouldThrowIfClientNotFound()
    {
        $defaultDriver = $this->createClientDriverMock();
        $defaultDriver
            ->expects($this->never())
            ->method('setupBroker')
        ;

        $command = new SetupBrokerCommand(new Container([
            'enqueue.client.default.driver' => $defaultDriver,
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client "foo" is not supported.');
        $tester->execute([
            '--client' => 'foo',
        ]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DriverInterface
     */
    private function createClientDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
