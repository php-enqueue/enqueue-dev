<?php
namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class QueuesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get('enqueue.client.meta.queues_command');

        $this->assertInstanceOf(QueuesCommand::class, $command);
    }

    public function testShouldDisplayRegisteredDestionations()
    {
        $command = $this->container->get('enqueue.client.meta.queues_command');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertContains(' default ', $display);
        $this->assertContains('enqueue.app.default', $display);
        $this->assertContains('enqueue.client.router_processor', $display);
    }
}
