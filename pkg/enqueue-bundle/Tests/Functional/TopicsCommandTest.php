<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\Config;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class TopicsCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get('enqueue.client.meta.topics_command');

        $this->assertInstanceOf(TopicsCommand::class, $command);
    }

    public function testShouldDisplayRegisteredTopics()
    {
        $command = $this->container->get('enqueue.client.meta.topics_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains('__router__', $display);
        $this->assertContains('enqueue.client.router_processor', $display);
    }

    public function testShouldDisplayCommands()
    {
        $command = $this->container->get('enqueue.client.meta.topics_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(Config::COMMAND_TOPIC, $display);
        $this->assertContains('test_command_subscriber', $display);
    }
}
