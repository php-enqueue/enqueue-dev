<?php

namespace Enqueue\AsyncCommand\Tests;

use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;

class RunCommandProcessorTest extends TestCase
{
    use ReadAttributeTrait;

    public function testShouldImplementProcessorInterface()
    {
        $rc = new \ReflectionClass(RunCommandProcessor::class);

        $this->assertTrue($rc->implementsInterface(Processor::class));
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

    public function testCouldBeConstructedWithTimeoutAsSecondArgument()
    {
        $processor = new RunCommandProcessor('aProjectDir', 60);

        $this->assertAttributeSame(60, 'timeout', $processor);
    }
}
