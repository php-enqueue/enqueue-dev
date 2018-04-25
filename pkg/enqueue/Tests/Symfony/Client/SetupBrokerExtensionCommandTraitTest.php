<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Tests\Symfony\Client\Mock\SetupBrokerExtensionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetupBrokerExtensionCommandTraitTest extends TestCase
{
    public function testShouldAddExtensionOptions()
    {
        $command = new SetupBrokerExtensionCommand('name');

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(1, $options);
        $this->assertArrayHasKey('setup-broker', $options);
    }

    public function testShouldAddExtensionIfOptionExists()
    {
        $command = new SetupBrokerExtensionCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--setup-broker' => true,
        ]);

        $result = $command->getExtension();

        $this->assertInstanceOf(SetupBrokerExtension::class, $result);
    }

    public function testShouldNotAddExtensionIfOptionDoesNotExists()
    {
        $command = new SetupBrokerExtensionCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $result = $command->getExtension();

        $this->assertNull($result);
    }
}
