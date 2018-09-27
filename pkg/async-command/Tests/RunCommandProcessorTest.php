<?php

namespace Enqueue\AsyncCommand\Tests;

use Enqueue\AsyncCommand\Commands;
use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;

class RunCommandProcessorTest extends TestCase
{
    public function testShouldImplementProcessorInterface()
    {
        $rc = new \ReflectionClass(RunCommandProcessor::class);

        $this->assertTrue($rc->implementsInterface(PsrProcessor::class));
    }

    public function testShouldImplementCommandSubscriberInterfaceInterface()
    {
        $rc = new \ReflectionClass(RunCommandProcessor::class);

        $this->assertTrue($rc->implementsInterface(CommandSubscriberInterface::class));
    }

    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(RunCommandProcessor::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testCouldBeConstructedWithProjectDirAsFirstArgument()
    {
        $processor = new RunCommandProcessor('aProjectDir');

        $this->assertAttributeSame('aProjectDir', 'projectDir', $processor);
    }

    public function testShouldSubscribeOnRunCommand()
    {
        $subscription = RunCommandProcessor::getSubscribedCommand();

        $this->assertSame([
            'command' => Commands::RUN_COMMAND,
            'queue' => Commands::RUN_COMMAND,
            'prefix_queue' => false,
            'exclusive' => true,
        ], $subscription);
    }
}
