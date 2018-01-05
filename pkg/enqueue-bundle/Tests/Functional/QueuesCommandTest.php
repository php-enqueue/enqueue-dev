<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\RouterProcessor;
use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @group functional
 */
class QueuesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get(QueuesCommand::class);

        $this->assertInstanceOf(QueuesCommand::class, $command);
    }

    public function testShouldDisplayRegisteredQueues()
    {
        $command = $this->container->get(QueuesCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(' default ', $display);
        $this->assertContains('enqueue.app.default', $display);

        $displayId = RouterProcessor::class;
        if (30300 > Kernel::VERSION_ID) {
            // Symfony 3.2 and below make service identifiers lowercase, so we do the same.
            // To be removed when dropping support for Symfony < 3.3.
            $displayId = strtolower($displayId);
        }

        $this->assertContains($displayId, $display);
    }

    public function testShouldDisplayRegisteredCommand()
    {
        $command = $this->container->get(QueuesCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains('test_command_subscriber', $display);
    }
}
