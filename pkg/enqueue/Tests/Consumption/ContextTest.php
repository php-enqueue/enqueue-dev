<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithSessionAsFirstArgument()
    {
        new Context($this->createPsrContext());
    }

    public function testShouldAllowGetSessionSetInConstructor()
    {
        $psrContext = $this->createPsrContext();

        $context = new Context($psrContext);

        $this->assertSame($psrContext, $context->getPsrContext());
    }

    public function testShouldAllowGetMessageConsumerPreviouslySet()
    {
        $messageConsumer = $this->createPsrConsumer();

        $context = new Context($this->createPsrContext());
        $context->setPsrConsumer($messageConsumer);

        $this->assertSame($messageConsumer, $context->getPsrConsumer());
    }

    public function testThrowOnTryToChangeMessageConsumerIfAlreadySet()
    {
        $messageConsumer = $this->createPsrConsumer();
        $anotherMessageConsumer = $this->createPsrConsumer();

        $context = new Context($this->createPsrContext());

        $context->setPsrConsumer($messageConsumer);

        $this->expectException(IllegalContextModificationException::class);

        $context->setPsrConsumer($anotherMessageConsumer);
    }

    public function testShouldAllowGetMessageProducerPreviouslySet()
    {
        $processorMock = $this->createProcessorMock();

        $context = new Context($this->createPsrContext());
        $context->setPsrProcessor($processorMock);

        $this->assertSame($processorMock, $context->getPsrProcessor());
    }

    public function testThrowOnTryToChangeProcessorIfAlreadySet()
    {
        $processor = $this->createProcessorMock();
        $anotherProcessor = $this->createProcessorMock();

        $context = new Context($this->createPsrContext());

        $context->setPsrProcessor($processor);

        $this->expectException(IllegalContextModificationException::class);

        $context->setPsrProcessor($anotherProcessor);
    }

    public function testShouldAllowGetLoggerPreviouslySet()
    {
        $logger = new NullLogger();

        $context = new Context($this->createPsrContext());
        $context->setLogger($logger);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldSetExecutionInterruptedToFalseInConstructor()
    {
        $context = new Context($this->createPsrContext());

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testShouldAllowGetPreviouslySetMessage()
    {
        /** @var PsrMessage $message */
        $message = $this->createMock(PsrMessage::class);

        $context = new Context($this->createPsrContext());

        $context->setPsrMessage($message);

        $this->assertSame($message, $context->getPsrMessage());
    }

    public function testThrowOnTryToChangeMessageIfAlreadySet()
    {
        /** @var PsrMessage $message */
        $message = $this->createMock(PsrMessage::class);

        $context = new Context($this->createPsrContext());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The message could be set once');

        $context->setPsrMessage($message);
        $context->setPsrMessage($message);
    }

    public function testShouldAllowGetPreviouslySetException()
    {
        $exception = new \Exception();

        $context = new Context($this->createPsrContext());

        $context->setException($exception);

        $this->assertSame($exception, $context->getException());
    }

    public function testShouldAllowGetPreviouslySetResult()
    {
        $result = 'aResult';

        $context = new Context($this->createPsrContext());

        $context->setResult($result);

        $this->assertSame($result, $context->getResult());
    }

    public function testThrowOnTryToChangeResultIfAlreadySet()
    {
        $result = 'aResult';

        $context = new Context($this->createPsrContext());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The result modification is not allowed');

        $context->setResult($result);
        $context->setResult($result);
    }

    public function testShouldAllowGetPreviouslySetExecutionInterrupted()
    {
        $context = new Context($this->createPsrContext());

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        $context->setExecutionInterrupted(true);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testThrowOnTryToRollbackExecutionInterruptedIfAlreadySetToTrue()
    {
        $context = new Context($this->createPsrContext());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The execution once interrupted could not be roll backed');

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(false);
    }

    public function testNotThrowOnSettingExecutionInterruptedToTrueIfAlreadySetToTrue()
    {
        $context = new Context($this->createPsrContext());

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(true);
    }

    public function testShouldAllowGetPreviouslySetLogger()
    {
        $expectedLogger = new NullLogger();

        $context = new Context($this->createPsrContext());

        $context->setLogger($expectedLogger);

        $this->assertSame($expectedLogger, $context->getLogger());
    }

    public function testThrowOnSettingLoggerIfAlreadySet()
    {
        $context = new Context($this->createPsrContext());

        $context->setLogger(new NullLogger());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The logger modification is not allowed');

        $context->setLogger(new NullLogger());
    }

    public function testShouldAllowGetPreviouslySetQueue()
    {
        $context = new Context($this->createPsrContext());

        $context->setPsrQueue($queue = new NullQueue(''));

        $this->assertSame($queue, $context->getPsrQueue());
    }

    public function testThrowOnSettingQueueNameIfAlreadySet()
    {
        $context = new Context($this->createPsrContext());

        $context->setPsrQueue(new NullQueue(''));

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The queue modification is not allowed');

        $context->setPsrQueue(new NullQueue(''));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    protected function createPsrContext()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrConsumer
     */
    protected function createPsrConsumer()
    {
        return $this->createMock(PsrConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(PsrProcessor::class);
    }
}
