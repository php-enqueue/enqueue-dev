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
        $command = static::$container->get(ConsumeMessagesCommand::class);

        $this->assertInstanceOf(ConsumeMessagesCommand::class, $command);
    }
}
