<?php

namespace Enqueue\AsyncCommand\Tests;

use Enqueue\AsyncCommand\RunCommand;
use PHPUnit\Framework\TestCase;

class RunCommandTest extends TestCase
{
    public function testShouldImplementJsonSerializableInterface()
    {
        $rc = new \ReflectionClass(RunCommand::class);

        $this->assertTrue($rc->implementsInterface(\JsonSerializable::class));
    }

    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(RunCommand::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testShouldAllowGetCommandSetInConstructor()
    {
        $command = new RunCommand('theCommand');

        $this->assertSame('theCommand', $command->getCommand());
    }

    public function testShouldReturnEmptyArrayByDefaultOnGetArguments()
    {
        $command = new RunCommand('aCommand');

        $this->assertSame([], $command->getArguments());
    }

    public function testShouldReturnEmptyArrayByDefaultOnGetOptions()
    {
        $command = new RunCommand('aCommand');

        $this->assertSame([], $command->getOptions());
    }

    public function testShouldReturnArgumentsSetInConstructor()
    {
        $command = new RunCommand('aCommand', ['theArgument' => 'theValue']);

        $this->assertSame(['theArgument' => 'theValue'], $command->getArguments());
    }

    public function testShouldReturnOptionsSetInConstructor()
    {
        $command = new RunCommand('aCommand', [], ['theOption' => 'theValue']);

        $this->assertSame(['theOption' => 'theValue'], $command->getOptions());
    }

    public function testShouldSerializeAndUnserialzeCommand()
    {
        $command = new RunCommand(
            'theCommand',
            ['theArgument' => 'theValue'],
            ['theOption' => 'theValue']
        );

        $jsonCommand = json_encode($command);

        // guard
        $this->assertNotEmpty($jsonCommand);

        $unserializedCommand = RunCommand::jsonUnserialize($jsonCommand);

        $this->assertInstanceOf(RunCommand::class, $unserializedCommand);
        $this->assertSame('theCommand', $unserializedCommand->getCommand());
        $this->assertSame(['theArgument' => 'theValue'], $unserializedCommand->getArguments());
        $this->assertSame(['theOption' => 'theValue'], $unserializedCommand->getOptions());
    }

    public function testThrowExceptionIfInvalidJsonGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        RunCommand::jsonUnserialize('{]');
    }
}
