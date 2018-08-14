<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\RouterProcessor;
use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class QueuesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = static::$container->get(QueuesCommand::class);

        $this->assertInstanceOf(QueuesCommand::class, $command);
    }

    public function testShouldDisplayRegisteredQueues()
    {
        $command = static::$container->get(QueuesCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(' default ', $display);
        $this->assertContains('enqueue.app.default', $display);
        $this->assertContains(RouterProcessor::class, $display);
    }

    public function testShouldDisplayRegisteredCommand()
    {
        $command = static::$container->get(QueuesCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains('test_command_subscriber', $display);
    }
}
