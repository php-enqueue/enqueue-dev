<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument()
    {
        new LoggerExtension($this->createLogger());
    }

    public function testShouldSetLoggerToContextOnStart()
    {
        $logger = $this->createLogger();

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), new NullLogger(), [], 0, 0, 0);

        $extension->onStart($context);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAddInfoMessageOnStart()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Set context\'s logger'))
        ;

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), new NullLogger(), [], 0, 0, 0);

        $extension->onStart($context);
    }

    public function testShouldLogRejectMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $context = new Context($this->createContextMock());
        $context->setResult(Result::reject('reason'));
        $context->setInteropMessage($message);

        $extension->onPostReceived($context);
    }

    public function testShouldLogRequeueMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $context = new Context($this->createContextMock());
        $context->setResult(Result::requeue('reason'));
        $context->setInteropMessage($message);

        $extension->onPostReceived($context);
    }

    public function testShouldNotLogRequeueMessageStatusIfReasonIsEmpty()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createContextMock());
        $context->setResult(Result::requeue());

        $extension->onPostReceived($context);
    }

    public function testShouldLogAckMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $context = new Context($this->createContextMock());
        $context->setResult(Result::ack('reason'));
        $context->setInteropMessage($message);

        $extension->onPostReceived($context);
    }

    public function testShouldNotLogAckMessageStatusIfReasonIsEmpty()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createContextMock());
        $context->setResult(Result::ack());

        $extension->onPostReceived($context);
    }

    public function testShouldNotSetLoggerIfOneHasBeenSetOnStart()
    {
        $logger = $this->createLogger();

        $alreadySetLogger = $this->createLogger();
        $alreadySetLogger
            ->expects($this->once())
            ->method('debug')
            ->with(sprintf(
                'Skip setting context\'s logger "%s". Another one "%s" has already been set.',
                get_class($logger),
                get_class($alreadySetLogger)
            ))
        ;

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), $alreadySetLogger, [], 0, 0, 0);

        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    protected function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    protected function createConsumerMock()
    {
        return $this->createMock(Consumer::class);
    }
}
