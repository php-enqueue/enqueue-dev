<?php

namespace Enqueue\AsyncCommand\Tests\Functional;

use Enqueue\AsyncCommand\CommandResult;
use Enqueue\AsyncCommand\RunCommand;
use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class UseCasesTest extends TestCase
{
    public function testRunSimpleCommandAndReturnOutput()
    {
        $runCommand = new RunCommand('foo');

        $psrMessage = new NullMessage(json_encode($runCommand));
        $psrMessage->setReplyTo('aReplyToQueue');

        $processor = new RunCommandProcessor(__DIR__);

        $result = $processor->process($psrMessage, new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertInstanceOf(PsrMessage::class, $result->getReply());

        $replyMessage = $result->getReply();

        $commandResult = CommandResult::jsonUnserialize($replyMessage->getBody());

        $this->assertSame(123, $commandResult->getExitCode());
        $this->assertSame('Command Output', $commandResult->getOutput());
        $this->assertSame('Command Error Output', $commandResult->getErrorOutput());
    }
}
