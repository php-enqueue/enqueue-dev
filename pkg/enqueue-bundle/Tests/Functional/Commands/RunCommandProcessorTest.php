<?php

namespace Enqueue\Bundle\Tests\Functional\Commands;

use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class RunCommandProcessorTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        /** @var RunCommandProcessor $processor */
        $processor = static::$container->get('enqueue.async_command.run_command_processor');

        $this->assertInstanceOf(RunCommandProcessor::class, $processor);
    }
}
