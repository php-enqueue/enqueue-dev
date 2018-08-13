<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\Config;
use Enqueue\Client\RouterProcessor;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class TopicsCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = static::$container->get(TopicsCommand::class);

        $this->assertInstanceOf(TopicsCommand::class, $command);
    }

    public function testShouldDisplayRegisteredTopics()
    {
        $command = static::$container->get(TopicsCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains('__router__', $display);
        $this->assertContains(RouterProcessor::class, $display);
    }

    public function testShouldDisplayCommands()
    {
        $command = static::$container->get(TopicsCommand::class);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(Config::COMMAND_TOPIC, $display);
        $this->assertContains('test_command_subscriber', $display);
    }
}
