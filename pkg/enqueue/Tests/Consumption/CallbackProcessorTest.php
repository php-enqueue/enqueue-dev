<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;

class CallbackProcessorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorInterface()
    {
        $this->assertClassImplements(PsrProcessor::class, CallbackProcessor::class);
    }

    public function testCouldBeConstructedWithCallableAsArgument()
    {
        new CallbackProcessor(function () {
        });
    }

    public function testShouldCallCallbackAndProxyItsReturnedValue()
    {
        $expectedMessage = new NullMessage();
        $expectedContext = new NullContext();

        $processor = new CallbackProcessor(function ($message, $context) use ($expectedMessage, $expectedContext) {
            $this->assertSame($expectedMessage, $message);
            $this->assertSame($expectedContext, $context);

            return 'theStatus';
        });

        $this->assertSame('theStatus', $processor->process($expectedMessage, $expectedContext));
    }
}
