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
        $command = static::$container->get('test.enqueue.client.default.routes_command');

        $this->assertInstanceOf(RoutesCommand::class, $command);
    }

    public function testShouldDisplayRegisteredTopics()
    {
        /** @var RoutesCommand $command */
        $command = static::$container->get('test.enqueue.client.default.routes_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $expected = <<<'OUTPUT'
| topic   | theTopic                                            | default (prefixed)                     | test_topic_subscriber_processor                         | (hidden) |
OUTPUT;

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertContains($expected, $tester->getDisplay());
    }

    public function testShouldDisplayCommands()
    {
        /** @var RoutesCommand $command */
        $command = static::$container->get('test.enqueue.client.default.routes_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $expected = <<<'OUTPUT'
| command | theCommand                                          | default (prefixed)                     | test_command_subscriber_processor                       | (hidden) |
OUTPUT;

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertContains($expected, $tester->getDisplay());
    }
}
