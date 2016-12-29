<?php
namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Symfony\Client\ProduceMessageCommand;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class ProduceMessageCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get('enqueue.client.produce_message_command');

        $this->assertInstanceOf(ProduceMessageCommand::class, $command);
    }
}
