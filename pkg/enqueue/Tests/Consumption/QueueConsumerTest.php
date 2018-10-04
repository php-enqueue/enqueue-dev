<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\BoundProcessor;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullQueue;
use Enqueue\Tests\Consumption\Mock\BreakCycleExtension;
use Enqueue\Tests\Consumption\Mock\DummySubscriptionConsumer;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class QueueConsumerTest extends TestCase
{
    public function testCouldBeConstructedWithAllArguments()
    {
        new QueueConsumer($this->createContextStub(), null, [], null, 0, 0);
    }

    public function testCouldBeConstructedWithContextOnly()
    {
        new QueueConsumer($this->createContextStub());
    }

    public function testCouldBeConstructedWithContextAndSingleExtension()
    {
        new QueueConsumer($this->createContextStub(), $this->createExtension());
    }

    public function testShouldSetEmptyArrayToBoundProcessorsPropertyInConstructor()
    {
        $consumer = new QueueConsumer($this->createContextStub(), null, [], null, 0, 0);

        $this->assertAttributeSame([], 'boundProcessors', $consumer);
    }

    public function testShouldSetProvidedBoundProcessorsToThePropertyInConstructor()
    {
        $boundProcessors = [
            new BoundProcessor(new NullQueue('foo'), $this->createProcessorMock()),
            new BoundProcessor(new NullQueue('bar'), $this->createProcessorMock()),
        ];

        $consumer = new QueueConsumer($this->createContextStub(), null, $boundProcessors, null, 0, 0);

        $this->assertAttributeSame($boundProcessors, 'boundProcessors', $consumer);
    }

    public function testShouldSetNullLoggerIfNoneProvidedInConstructor()
    {
        $consumer = new QueueConsumer($this->createContextStub(), null, [], null, 0, 0);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $consumer);
    }

    public function testShouldSetProvidedLoggerToThePropertyInConstructor()
    {
        $expectedLogger = $this->createMock(LoggerInterface::class);

        $consumer = new QueueConsumer($this->createContextStub(), null, [], $expectedLogger, 0, 0);

        $this->assertAttributeSame($expectedLogger, 'logger', $consumer);
    }

    public function testShouldAllowGetContextSetInConstructor()
    {
        $expectedContext = $this->createContextStub();

        $consumer = new QueueConsumer($expectedContext, null, [], null, 0, 0);

        $this->assertSame($expectedContext, $consumer->getContext());
    }

    public function testThrowIfQueueNameEmptyOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createContextStub());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue name must be not empty.');
        $consumer->bind(new NullQueue(''), $processorMock);
    }

    public function testThrowIfQueueAlreadyBoundToProcessorOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createContextStub());

        $consumer->bind(new NullQueue('theQueueName'), $processorMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue was already bound.');
        $consumer->bind(new NullQueue('theQueueName'), $processorMock);
    }

    public function testShouldAllowBindProcessorToQueue()
    {
        $queue = new NullQueue('theQueueName');
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createContextStub());

        $consumer->bind($queue, $processorMock);

        $this->assertAttributeEquals(
            ['theQueueName' => new BoundProcessor($queue, $processorMock)],
            'boundProcessors',
            $consumer
        );
    }

    public function testThrowIfQueueNeitherInstanceOfQueueNorString()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createContextStub());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The argument must be an instance of Interop\Queue\Queue but got stdClass.');
        $consumer->bind(new \stdClass(), $processorMock);
    }

    public function testCouldSetGetIdleTime()
    {
        $consumer = new QueueConsumer($this->createContextStub());

        $consumer->setIdleTime(123456);

        $this->assertSame(123456, $consumer->getIdleTime());
    }

    public function testCouldSetGetReceiveTimeout()
    {
        $consumer = new QueueConsumer($this->createContextStub());

        $consumer->setReceiveTimeout(123456);

        $this->assertSame(123456, $consumer->getReceiveTimeout());
    }

    public function testShouldAllowBindCallbackToQueueName()
    {
        $callback = function () {
        };

        $queueName = 'theQueueName';
        $queue = new NullQueue($queueName);

        $context = $this->createContextWithoutSubscriptionConsumerMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($queueName)
            ->willReturn($queue)
        ;

        $consumer = new QueueConsumer($context);

        $consumer->bindCallback($queueName, $callback);

        $boundProcessors = $this->readAttribute($consumer, 'boundProcessors');

        $this->assertInternalType('array', $boundProcessors);
        $this->assertCount(1, $boundProcessors);
        $this->assertArrayHasKey($queueName, $boundProcessors);

        $this->assertInstanceOf(BoundProcessor::class, $boundProcessors[$queueName]);
        $this->assertSame($queue, $boundProcessors[$queueName]->getQueue());
        $this->assertInstanceOf(CallbackProcessor::class, $boundProcessors[$queueName]->getProcessor());
    }

    public function testShouldReturnSelfOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createContextStub());

        $this->assertSame($consumer, $consumer->bind(new NullQueue('foo_queue'), $processorMock));
    }

    public function testShouldUseContextSubscriptionConsumerIfSupport()
    {
        $expectedQueue = new NullQueue('theQueueName');

        $contextSubscriptionConsumer = $this->createSubscriptionConsumerMock();
        $contextSubscriptionConsumer
            ->expects($this->once())
            ->method('consume')
            ->willReturn(null)
        ;

        $fallbackSubscriptionConsumer = $this->createSubscriptionConsumerMock();
        $fallbackSubscriptionConsumer
            ->expects($this->never())
            ->method('consume')
        ;

        $contextMock = $this->createMock(InteropContext::class);
        $contextMock
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($expectedQueue))
            ->willReturn($this->createConsumerStub())
        ;
        $contextMock
            ->expects($this->once())
            ->method('createSubscriptionConsumer')
            ->willReturn($contextSubscriptionConsumer)
        ;

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($fallbackSubscriptionConsumer);
        $queueConsumer->bind($expectedQueue, $processorMock);
        $queueConsumer->consume();
    }

    public function testShouldUseFallbackSubscriptionConsumerIfNotSupported()
    {
        $expectedQueue = new NullQueue('theQueueName');

        $contextSubscriptionConsumer = $this->createSubscriptionConsumerMock();
        $contextSubscriptionConsumer
            ->expects($this->never())
            ->method('consume')
        ;

        $fallbackSubscriptionConsumer = $this->createSubscriptionConsumerMock();
        $fallbackSubscriptionConsumer
            ->expects($this->once())
            ->method('consume')
            ->willReturn(null)
        ;

        $contextMock = $this->createContextWithoutSubscriptionConsumerMock();
        $contextMock
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($expectedQueue))
            ->willReturn($this->createConsumerStub())
        ;
        $contextMock
            ->expects($this->once())
            ->method('createSubscriptionConsumer')
            ->willThrowException(SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt())
        ;

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($fallbackSubscriptionConsumer);
        $queueConsumer->bind($expectedQueue, $processorMock);
        $queueConsumer->consume();
    }

    public function testShouldSubscribeToGivenQueueWithExpectedTimeout()
    {
        $expectedQueue = new NullQueue('theQueueName');

        $subscriptionConsumerMock = $this->createSubscriptionConsumerMock();
        $subscriptionConsumerMock
            ->expects($this->once())
            ->method('consume')
            ->with(12345)
            ->willReturn(null)
        ;

        $contextMock = $this->createContextWithoutSubscriptionConsumerMock();
        $contextMock
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($expectedQueue))
            ->willReturn($this->createConsumerStub())
        ;

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(1), [], null, 0, 12345);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind($expectedQueue, $processorMock);
        $queueConsumer->consume();
    }

    public function testShouldSubscribeToGivenQueueAndQuitAfterFifthIdleCycle()
    {
        $expectedQueue = new NullQueue('theQueueName');

        $subscriptionConsumerMock = $this->createSubscriptionConsumerMock();
        $subscriptionConsumerMock
            ->expects($this->exactly(5))
            ->method('consume')
            ->willReturn(null)
        ;

        $contextMock = $this->createContextWithoutSubscriptionConsumerMock();
        $contextMock
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($expectedQueue))
            ->willReturn($this->createConsumerStub())
        ;

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(5));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind($expectedQueue, $processorMock);
        $queueConsumer->consume();
    }

    public function testShouldProcessFiveMessagesAndQuit()
    {
        $fooQueue = new NullQueue('foo_queue');

        $firstMessageMock = $this->createMessageMock();
        $secondMessageMock = $this->createMessageMock();
        $thirdMessageMock = $this->createMessageMock();
        $fourthMessageMock = $this->createMessageMock();
        $fifthMessageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($firstMessageMock, 'foo_queue');
        $subscriptionConsumerMock->addMessage($secondMessageMock, 'foo_queue');
        $subscriptionConsumerMock->addMessage($thirdMessageMock, 'foo_queue');
        $subscriptionConsumerMock->addMessage($fourthMessageMock, 'foo_queue');
        $subscriptionConsumerMock->addMessage($fifthMessageMock, 'foo_queue');

        $contextStub = $this->createContextStub();

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->exactly(5))
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(5));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind($fooQueue, $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldAckMessageIfProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');
        $consumerStub
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($messageMock))
        ;

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::ACK)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testThrowIfProcessorReturnNull()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(null)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Status is not supported');
        $queueConsumer->consume();
    }

    public function testShouldRejectMessageIfProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');
        $consumerStub
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($messageMock), false)
        ;

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::REJECT)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldRequeueMessageIfProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');
        $consumerStub
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($messageMock), true)
        ;

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::REQUEUE)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testThrowIfProcessorReturnInvalidStatus()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn('invalidStatus')
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Status is not supported: invalidStatus');
        $queueConsumer->consume();
    }

    public function testShouldNotPassMessageToProcessorIfItWasProcessedByExtension()
    {
        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setResult(Result::ACK);
            })
        ;

        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnStartExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnStartWithLoggerProvidedInConstructor()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $expectedLogger = $this->createMock(LoggerInterface::class);

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, [], $expectedLogger);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnIdleExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getProcessor());
                $this->assertNull($context->getConsumer());
                $this->assertNull($context->getInteropMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnBeforeReceiveExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $queue = new NullQueue('foo_queue');

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getProcessor());
                $this->assertNull($context->getConsumer());
                $this->assertNull($context->getInteropMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertNull($context->getInteropQueue());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind($queue, $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPreReceivedExtensionMethodWithExpectedContext()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnResultExtensionMethodWithExpectedContext()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPostReceivedExtensionMethodWithExpectedContext()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldAllowInterruptConsumingOnIdle()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getConsumer());
                $this->assertNull($context->getProcessor());
                $this->assertNull($context->getInteropMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldNotCloseContextWhenConsumptionInterrupted()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);
        $contextStub
            ->expects($this->never())
            ->method('close')
        ;

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldNotCloseContextWhenConsumptionInterruptedByException()
    {
        $expectedException = new \Exception();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($this->createMessageMock(), 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);
        $contextStub
            ->expects($this->never())
            ->method('close')
        ;

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException($expectedException)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        try {
            $queueConsumer->consume();
        } catch (\Exception $e) {
            $this->assertSame($expectedException, $e);
            $this->assertNull($e->getPrevious());

            return;
        }

        $this->fail('Exception throw is expected.');
    }

    public function testShouldSetMainExceptionAsPreviousToExceptionThrownOnInterrupt()
    {
        $mainException = new \Exception();
        $expectedException = new \Exception();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($this->createMessageMock(), 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException($mainException)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->willThrowException($expectedException)
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        try {
            $queueConsumer->consume();
        } catch (\Exception $e) {
            $this->assertSame($expectedException, $e);
            $this->assertSame($mainException, $e->getPrevious());

            return;
        }

        $this->fail('Exception throw is expected.');
    }

    public function testShouldAllowInterruptConsumingOnPreReceiveButProcessCurrentMessage()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldAllowInterruptConsumingOnResult()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldAllowInterruptConsumingOnPostReceive()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnInterruptedIfExceptionThrow()
    {
        $expectedException = new \Exception('Process failed');
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException($expectedException)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage,
                $expectedException
            ) {
                $this->assertSame($contextStub, $context->getInteropContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getInteropMessage());
                $this->assertSame($expectedException, $context->getException());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Process failed');
        $queueConsumer->consume();
    }

    public function testShouldCallExtensionPassedOnRuntime()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $runtimeExtension = $this->createExtension();
        $runtimeExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume(new ChainExtension([$runtimeExtension]));
    }

    public function testShouldChangeLoggerOnStart()
    {
        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        $expectedLogger = new NullLogger();

        $extension = $this->createExtension();
        $extension
            ->expects($this->atLeastOnce())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($expectedLogger) {
                $context->changeLogger($expectedLogger);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);

        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallProcessorAsMessageComeAlong()
    {
        $queue1 = new NullQueue('foo_queue');
        $queue2 = new NullQueue('bar_queue');

        $firstMessage = $this->createMessageMock();
        $secondMessage = $this->createMessageMock();
        $thirdMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($firstMessage, 'foo_queue');
        $subscriptionConsumerMock->addMessage($secondMessage, 'bar_queue');
        $subscriptionConsumerMock->addMessage($thirdMessage, 'foo_queue');

        $fooConsumerStub = $this->createConsumerStub($queue1);
        $barConsumerStub = $this->createConsumerStub($queue2);

        $consumers = [
            'foo_queue' => $fooConsumerStub,
            'bar_queue' => $barConsumerStub,
        ];

        $contextStub = $this->createContextWithoutSubscriptionConsumerMock();
        $contextStub
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $queueName) {
                return new NullQueue($queueName);
            })
        ;
        $contextStub
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturnCallback(function (Queue $queue) use ($consumers) {
                return $consumers[$queue->getQueueName()];
            })
        ;

        $processorMock = $this->createProcessorStub();
        $anotherProcessorMock = $this->createProcessorStub();

        /** @var Context[] $actualContexts */
        $actualContexts = [];

        $extension = $this->createExtension();
        $extension
            ->expects($this->any())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use (&$actualContexts) {
                $actualContexts[] = clone $context;
            })
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(3));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer
            ->bind($queue1, $processorMock)
            ->bind($queue2, $anotherProcessorMock)
        ;

        $queueConsumer->consume(new ChainExtension([$extension]));

        $this->assertCount(3, $actualContexts);

        $this->assertSame($firstMessage, $actualContexts[0]->getInteropMessage());
        $this->assertSame($secondMessage, $actualContexts[1]->getInteropMessage());
        $this->assertSame($thirdMessage, $actualContexts[2]->getInteropMessage());

        $this->assertSame($fooConsumerStub, $actualContexts[0]->getConsumer());
        $this->assertSame($barConsumerStub, $actualContexts[1]->getConsumer());
        $this->assertSame($fooConsumerStub, $actualContexts[2]->getConsumer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextWithoutSubscriptionConsumerMock(): InteropContext
    {
        $contextMock = $this->createMock(InteropContext::class);
        $contextMock
            ->expects($this->any())
            ->method('createSubscriptionConsumer')
            ->willThrowException(SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt())
        ;

        return $contextMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    private function createContextStub(Consumer $consumer = null): InteropContext
    {
        $context = $this->createContextWithoutSubscriptionConsumerMock();
        $context
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $queueName) {
                return new NullQueue($queueName);
            })
        ;
        $context
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturnCallback(function (Queue $queue) use ($consumer) {
                return $consumer ?: $this->createConsumerStub($queue);
            })
        ;

        $context
            ->expects($this->any())
            ->method('close')
        ;

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Processor
     */
    private function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Processor
     */
    private function createProcessorStub()
    {
        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->any())
            ->method('process')
            ->willReturn(Result::ACK)
        ;

        return $processorMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    private function createMessageMock(): Message
    {
        return $this->createMock(Message::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    private function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @param null|mixed $queue
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    private function createConsumerStub($queue = null): Consumer
    {
        if (is_string($queue)) {
            $queue = new NullQueue($queue);
        }

        $consumerMock = $this->createMock(Consumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $consumerMock;
    }

    /**
     * @return SubscriptionConsumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }
}
