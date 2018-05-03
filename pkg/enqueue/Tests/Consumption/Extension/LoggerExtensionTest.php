<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\OnStartContext;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ConsumptionContextMockTrait;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtensionTest extends TestCase
{
    use ClassExtensionTrait;
    use ConsumptionContextMockTrait;

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

        $context = new OnStartContext($this->createPsrContextMock(), new NullLogger(), [], []);

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

        $context = new OnStartContext($this->createPsrContextMock(), new NullLogger(), [], []);

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

        $context = new Context($this->createPsrContextMock());
        $context->setResult(Result::reject('reason'));
        $context->setPsrMessage($message);

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

        $context = new Context($this->createPsrContextMock());
        $context->setResult(Result::requeue('reason'));
        $context->setPsrMessage($message);

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

        $context = new Context($this->createPsrContextMock());
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

        $context = new Context($this->createPsrContextMock());
        $context->setResult(Result::ack('reason'));
        $context->setPsrMessage($message);

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

        $context = new Context($this->createPsrContextMock());
        $context->setResult(Result::ack());

        $extension->onPostReceived($context);
    }

    public function testShouldReplaceLoggerIfReplaceLoggerIsTrue()
    {
        $alreadySetLogger = $this->createLogger();

        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(sprintf(
                'Replace context\'s logger "%s" with "%s"',
                get_class($alreadySetLogger),
                get_class($logger)
            ))
        ;

        $extension = new LoggerExtension($logger, $replaceLogger = true);

        $context = new OnStartContext($this->createPsrContextMock(), $alreadySetLogger, [], []);

        $extension->onStart($context);
    }

    public function testShouldSkipSettingLoggerIfReplaceLoggerIsFalse()
    {
        $logger = $this->createLogger();

        $alreadySetLogger = $this->createLogger();
        $alreadySetLogger
            ->expects($this->once())
            ->method('debug')
            ->with(sprintf('Skip setting a logger "%s"', get_class($logger)))
        ;

        $extension = new LoggerExtension($logger, $replaceLogger = false);

        $context = new OnStartContext($this->createPsrContextMock(), $alreadySetLogger, [], []);

        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    protected function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrConsumer
     */
    protected function createConsumerMock()
    {
        return $this->createMock(PsrConsumer::class);
    }
}
