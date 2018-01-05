<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\Config;
use Enqueue\Client\RouterProcessor;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @group functional
 */
class TopicsCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get(TopicsCommand::class);

        $this->assertInstanceOf(TopicsCommand::class, $command);
    }

    public function testShouldDisplayRegisteredTopics()
    {
        $command = $this->container->get(TopicsCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains('__router__', $display);

        $displayId = RouterProcessor::class;
        if (30300 > Kernel::VERSION_ID) {
            // Symfony 3.2 and below make service identifiers lowercase, so we do the same.
            // To be removed when dropping support for Symfony < 3.3.
            $displayId = strtolower($displayId);
        }

        $this->assertContains($displayId, $display);
    }

    public function testShouldDisplayCommands()
    {
        $command = $this->container->get(TopicsCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(Config::COMMAND_TOPIC, $display);
        $this->assertContains('test_command_subscriber', $display);
    }
}
