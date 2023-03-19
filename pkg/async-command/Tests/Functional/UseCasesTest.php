<?php

namespace Enqueue\AsyncCommand\Tests\Functional;

use Enqueue\AsyncCommand\CommandResult;
use Enqueue\AsyncCommand\RunCommand;
use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Consumption\Result;
use Enqueue\NoEffect\NullContext;
use Enqueue\NoEffect\NullMessage;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class UseCasesTest extends TestCase
{
    public function testRunSimpleCommandAndReturnOutput()
    {
        $runCommand = new RunCommand('foo');

        $Message = new NullMessage(json_encode($runCommand));
        $Message->setReplyTo('aReplyToQueue');

        $processor = new RunCommandProcessor(__DIR__);

        $result = $processor->process($Message, new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertInstanceOf(Message::class, $result->getReply());

        $replyMessage = $result->getReply();

        $commandResult = CommandResult::jsonUnserialize($replyMessage->getBody());

        $this->assertSame(123, $commandResult->getExitCode());
        $this->assertSame('Command Output', $commandResult->getOutput());
        $this->assertSame('Command Error Output', $commandResult->getErrorOutput());
    }
}
