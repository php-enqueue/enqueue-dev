<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Symfony\Client\ConsumeMessagesCommand;

/**
 * @group functional
 */
class ConsumeMessagesCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get(ConsumeMessagesCommand::class);

        $this->assertInstanceOf(ConsumeMessagesCommand::class, $command);
    }
}
