<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Symfony\Client\SetupBrokerCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetupBrokerCommandTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new \Enqueue\Symfony\Client\SetupBrokerCommand($this->createClientDriverMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SetupBrokerCommand($this->createClientDriverMock());

        $this->assertEquals('enqueue:setup-broker', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new SetupBrokerCommand($this->createClientDriverMock());

        $this->assertEquals(['enq:sb'], $command->getAliases());
    }

    public function testShouldCreateQueues()
    {
        $driver = $this->createClientDriverMock();
        $driver
            ->expects($this->once())
            ->method('setupBroker')
        ;

        $command = new SetupBrokerCommand($driver);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertContains('Setup Broker', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createClientDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
