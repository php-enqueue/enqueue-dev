<?php
namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\CallbackMessageProcessor;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;

class CallbackMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, CallbackMessageProcessor::class);
    }

    public function testCouldBeConstructedWithCallableAsArgument()
    {
        new CallbackMessageProcessor(function () {
        });
    }

    public function testShouldCallCallbackAndProxyItsReturnedValue()
    {
        $expectedMessage = new NullMessage();
        $expectedContext = new NullContext();

        $processor = new CallbackMessageProcessor(function ($message, $context) use ($expectedMessage, $expectedContext) {
            $this->assertSame($expectedMessage, $message);
            $this->assertSame($expectedContext, $context);

            return 'theStatus';
        });

        $this->assertSame('theStatus', $processor->process($expectedMessage, $expectedContext));
    }
}
