<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Exception\IllegalContextModificationException;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithSessionAsFirstArgument()
    {
        new Context($this->createContext());
    }

    public function testShouldAllowGetSessionSetInConstructor()
    {
        $interopContext = $this->createContext();

        $context = new Context($interopContext);

        $this->assertSame($interopContext, $context->getInteropContext());
    }

    public function testShouldAllowGetMessageConsumerPreviouslySet()
    {
        $messageConsumer = $this->createConsumer();

        $context = new Context($this->createContext());
        $context->setConsumer($messageConsumer);

        $this->assertSame($messageConsumer, $context->getConsumer());
    }

    public function testThrowOnTryToChangeMessageConsumerIfAlreadySet()
    {
        $messageConsumer = $this->createConsumer();
        $anotherMessageConsumer = $this->createConsumer();

        $context = new Context($this->createContext());

        $context->setConsumer($messageConsumer);

        $this->expectException(IllegalContextModificationException::class);

        $context->setConsumer($anotherMessageConsumer);
    }

    public function testShouldAllowGetMessageProducerPreviouslySet()
    {
        $processorMock = $this->createProcessorMock();

        $context = new Context($this->createContext());
        $context->setProcessor($processorMock);

        $this->assertSame($processorMock, $context->getProcessor());
    }

    public function testThrowOnTryToChangeProcessorIfAlreadySet()
    {
        $processor = $this->createProcessorMock();
        $anotherProcessor = $this->createProcessorMock();

        $context = new Context($this->createContext());

        $context->setProcessor($processor);

        $this->expectException(IllegalContextModificationException::class);

        $context->setProcessor($anotherProcessor);
    }

    public function testShouldAllowGetLoggerPreviouslySet()
    {
        $logger = new NullLogger();

        $context = new Context($this->createContext());
        $context->setLogger($logger);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldSetExecutionInterruptedToFalseInConstructor()
    {
        $context = new Context($this->createContext());

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testShouldAllowGetPreviouslySetMessage()
    {
        /** @var InteropMessage $message */
        $message = $this->createMock(InteropMessage::class);

        $context = new Context($this->createContext());

        $context->setInteropMessage($message);

        $this->assertSame($message, $context->getInteropMessage());
    }

    public function testThrowOnTryToChangeMessageIfAlreadySet()
    {
        /** @var InteropMessage $message */
        $message = $this->createMock(InteropMessage::class);

        $context = new Context($this->createContext());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The message could be set once');

        $context->setInteropMessage($message);
        $context->setInteropMessage($message);
    }

    public function testShouldAllowGetPreviouslySetException()
    {
        $exception = new \Exception();

        $context = new Context($this->createContext());

        $context->setException($exception);

        $this->assertSame($exception, $context->getException());
    }

    public function testShouldAllowGetPreviouslySetResult()
    {
        $result = 'aResult';

        $context = new Context($this->createContext());

        $context->setResult($result);

        $this->assertSame($result, $context->getResult());
    }

    public function testShouldAllowGetPreviouslySetExecutionInterrupted()
    {
        $context = new Context($this->createContext());

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        $context->setExecutionInterrupted(true);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testThrowOnTryToRollbackExecutionInterruptedIfAlreadySetToTrue()
    {
        $context = new Context($this->createContext());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The execution once interrupted could not be roll backed');

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(false);
    }

    public function testNotThrowOnSettingExecutionInterruptedToTrueIfAlreadySetToTrue()
    {
        $context = new Context($this->createContext());

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(true);
    }

    public function testShouldAllowGetPreviouslySetLogger()
    {
        $expectedLogger = new NullLogger();

        $context = new Context($this->createContext());

        $context->setLogger($expectedLogger);

        $this->assertSame($expectedLogger, $context->getLogger());
    }

    public function testThrowOnSettingLoggerIfAlreadySet()
    {
        $context = new Context($this->createContext());

        $context->setLogger(new NullLogger());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The logger modification is not allowed');

        $context->setLogger(new NullLogger());
    }

    public function testShouldAllowGetPreviouslySetQueue()
    {
        $context = new Context($this->createContext());

        $context->setInteropQueue($queue = new NullQueue(''));

        $this->assertSame($queue, $context->getInteropQueue());
    }

    public function testThrowOnSettingQueueNameIfAlreadySet()
    {
        $context = new Context($this->createContext());

        $context->setInteropQueue(new NullQueue(''));

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The queue modification is not allowed');

        $context->setInteropQueue(new NullQueue(''));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    protected function createContext(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    protected function createConsumer()
    {
        return $this->createMock(Consumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Processor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }
}
