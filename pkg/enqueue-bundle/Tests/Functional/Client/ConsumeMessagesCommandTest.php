<?php
namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Symfony\Client\ConsumeMessagesCommand;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class ConsumeMessagesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get('enqueue.client.consume_messages_command');

        $this->assertInstanceOf(ConsumeMessagesCommand::class, $command);
    }
}
