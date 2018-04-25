<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Symfony\Client\ProduceMessageCommand;

/**
 * @group functional
 */
class ProduceMessageCommandTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $command = $this->container->get(ProduceMessageCommand::class);

        $this->assertInstanceOf(ProduceMessageCommand::class, $command);
    }
}
