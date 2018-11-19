<?php

namespace Enqueue\AsyncCommand\Tests;

use Enqueue\AsyncCommand\CommandResult;
use PHPUnit\Framework\TestCase;

class CommandResultTest extends TestCase
{
    public function testShouldImplementJsonSerializableInterface()
    {
        $rc = new \ReflectionClass(CommandResult::class);

        $this->assertTrue($rc->implementsInterface(\JsonSerializable::class));
    }

    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(CommandResult::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testShouldAllowGetExitCodeSetInConstructor()
    {
        $result = new CommandResult(123, '', '');

        $this->assertSame(123, $result->getExitCode());
    }

    public function testShouldAllowGetOutputSetInConstructor()
    {
        $result = new CommandResult(0, 'theOutput', '');

        $this->assertSame('theOutput', $result->getOutput());
    }

    public function testShouldAllowGetErrorOutputSetInConstructor()
    {
        $result = new CommandResult(0, '', 'theErrorOutput');

        $this->assertSame('theErrorOutput', $result->getErrorOutput());
    }

    public function testShouldSerializeAndUnserialzeCommand()
    {
        $result = new CommandResult(123, 'theOutput', 'theErrorOutput');

        $jsonCommand = json_encode($result);

        // guard
        $this->assertNotEmpty($jsonCommand);

        $unserializedResult = CommandResult::jsonUnserialize($jsonCommand);

        $this->assertInstanceOf(CommandResult::class, $unserializedResult);
        $this->assertSame(123, $unserializedResult->getExitCode());
        $this->assertSame('theOutput', $unserializedResult->getOutput());
        $this->assertSame('theErrorOutput', $unserializedResult->getErrorOutput());
    }

    public function testThrowExceptionIfInvalidJsonGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        CommandResult::jsonUnserialize('{]');
    }
}
