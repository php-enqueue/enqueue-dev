<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Symfony\Client\RoutesCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class RoutesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = static::$container->get('test.enqueue.client.routes_command');

        $this->assertInstanceOf(RoutesCommand::class, $command);
    }

    public function testShouldDisplayRegisteredTopics()
    {
        /** @var RoutesCommand $command */
        $command = static::$container->get('test.enqueue.client.routes_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertContains('| topic', $tester->getDisplay());
        $this->assertContains('| theTopic', $tester->getDisplay());
        $this->assertContains('| default (prefixed)', $tester->getDisplay());
        $this->assertContains('| test_topic_subscriber_processor', $tester->getDisplay());
        $this->assertContains('| (hidden)', $tester->getDisplay());
    }

    public function testShouldDisplayCommands()
    {
        /** @var RoutesCommand $command */
        $command = static::$container->get('test.enqueue.client.routes_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertContains('| command', $tester->getDisplay());
        $this->assertContains('| theCommand', $tester->getDisplay());
        $this->assertContains('| test_command_subscriber_processor', $tester->getDisplay());
        $this->assertContains('| default (prefixed)', $tester->getDisplay());
        $this->assertContains('| (hidden)', $tester->getDisplay());
    }
}
