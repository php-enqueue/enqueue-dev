<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Symfony\Client\SetupBrokerCommand;
use Enqueue\Symfony\Client\SimpleSetupBrokerCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SimpleSetupBrokerCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfSetupBrokerCommand()
    {
        $this->assertClassExtends(SetupBrokerCommand::class, SimpleSetupBrokerCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(SimpleSetupBrokerCommand::class);
    }

    public function testCouldBeConstructedWithContainerAsFirstArgument()
    {
        new SimpleSetupBrokerCommand($this->createClientDriverMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SimpleSetupBrokerCommand($this->createClientDriverMock());

        $this->assertEquals('enqueue:setup-broker', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new SimpleSetupBrokerCommand($this->createClientDriverMock());

        $this->assertEquals(['enq:sb'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SimpleSetupBrokerCommand($this->createClientDriverMock());

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(1, $options);
        $this->assertArrayHasKey('client', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new SimpleSetupBrokerCommand($this->createClientDriverMock());

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

        $command = new SimpleSetupBrokerCommand($driver);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertContains('Broker set up', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DriverInterface
     */
    private function createClientDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
