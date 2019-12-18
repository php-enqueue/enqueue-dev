<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\BoundProcessor;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\Extension\ExitStatusExtension;
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
        new QueueConsumer($this->createContextStub(), null, [], null, 0);
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
        $consumer = new QueueConsumer($this->createContextStub(), null, [], null, 0);

        $this->assertAttributeSame([], 'boundProcessors', $consumer);
    }

    public function testShouldSetProvidedBoundProcessorsToThePropertyInConstructor()
    {
        $boundProcessors = [
            new BoundProcessor(new NullQueue('foo'), $this->createProcessorMock()),
            new BoundProcessor(new NullQueue('bar'), $this->createProcessorMock()),
        ];

        $consumer = new QueueConsumer($this->createContextStub(), null, $boundProcessors, null, 0);

        $this->assertAttributeSame($boundProcessors, 'boundProcessors', $consumer);
    }

    public function testShouldSetNullLoggerIfNoneProvidedInConstructor()
    {
        $consumer = new QueueConsumer($this->createContextStub(), null, [], null, 0);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $consumer);
    }

    public function testShouldSetProvidedLoggerToThePropertyInConstructor()
    {
        $expectedLogger = $this->createMock(LoggerInterface::class);

        $consumer = new QueueConsumer($this->createContextStub(), null, [], $expectedLogger, 0);

        $this->assertAttributeSame($expectedLogger, 'logger', $consumer);
    }

    public function testShouldAllowGetContextSetInConstructor()
    {
        $expectedContext = $this->createContextStub();

        $consumer = new QueueConsumer($expectedContext, null, [], null, 0);

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

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(1), [], null, 12345);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind($expectedQueue, $processorMock);
        $queueConsumer->consume();
    }

    public function testShouldSubscribeToGivenQueueAndQuitAfterFifthConsumeCycle()
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

    public function testShouldDoNothingIfProcessorReturnsAlreadyAcknowledged()
    {
        $messageMock = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($messageMock, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');
        $consumerStub
            ->expects($this->never())
            ->method('reject')
        ;
        $consumerStub
            ->expects($this->never())
            ->method('acknowledge')
        ;

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::ALREADY_ACKNOWLEDGED)
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
            ->method('onMessageReceived')
            ->with($this->isInstanceOf(MessageReceived::class))
            ->willReturnCallback(function (MessageReceived $context) {
                $context->setResult(Result::ack());
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

    public function testShouldCallOnInitLoggerExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $logger = $this->createMock(LoggerInterface::class);

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onInitLogger')
            ->with($this->isInstanceOf(InitLogger::class))
            ->willReturnCallback(function (InitLogger $context) use ($logger) {
                $this->assertSame($logger, $context->getLogger());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, [], $logger);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
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
                $this->assertSame($contextStub, $context->getContext());
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

    public function testShouldInterruptExecutionOnStart()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $expectedLogger = $this->createMock(LoggerInterface::class);

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onStart')
            ->willReturnCallback(function (Start $context) {
                $context->interruptExecution();
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onEnd')
        ;
        $extension
            ->expects($this->never())
            ->method('onPreConsume')
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, [], $expectedLogger);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallPreSubscribeExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreSubscribe')
            ->with($this->isInstanceOf(PreSubscribe::class))
            ->willReturnCallback(function (PreSubscribe $context) use ($contextStub, $consumerStub, $processorMock) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallPreSubscribeForEachBoundProcessor()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->exactly(3))
            ->method('onPreSubscribe')
            ->with($this->isInstanceOf(PreSubscribe::class))
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);
        $queueConsumer->bind(new NullQueue('bar_queue'), $processorMock);
        $queueConsumer->bind(new NullQueue('baz_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPostConsumeExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $subscriptionConsumer = new DummySubscriptionConsumer();

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPostConsume')
            ->with($this->isInstanceOf(PostConsume::class))
            ->willReturnCallback(function (PostConsume $context) use ($contextStub, $subscriptionConsumer) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($subscriptionConsumer, $context->getSubscriptionConsumer());
                $this->assertSame(1, $context->getCycle());
                $this->assertSame(0, $context->getReceivedMessagesCount());
                $this->assertGreaterThan(1, $context->getStartTime());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumer);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPreConsumeExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $queue = new NullQueue('foo_queue');

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreConsume')
            ->with($this->isInstanceOf(PreConsume::class))
            ->willReturnCallback(function (PreConsume $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertInstanceOf(SubscriptionConsumer::class, $context->getSubscriptionConsumer());
                $this->assertSame(10000, $context->getReceiveTimeout());
                $this->assertSame(1, $context->getCycle());
                $this->assertGreaterThan(0, $context->getStartTime());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind($queue, $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPreConsumeExpectedAmountOfTimes()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $queue = new NullQueue('foo_queue');

        $extension = $this->createExtension();
        $extension
            ->expects($this->exactly(3))
            ->method('onPreConsume')
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(3)]);
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
            ->method('onMessageReceived')
            ->with($this->isInstanceOf(MessageReceived::class))
            ->willReturnCallback(function (MessageReceived $context) use (
                $contextStub,
                $consumerStub,
                $processorMock,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($consumerStub, $context->getConsumer());
                $this->assertSame($processorMock, $context->getProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getResult());
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
            ->with($this->isInstanceOf(MessageResult::class))
            ->willReturnCallback(function (MessageResult $context) use ($contextStub, $expectedMessage) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertSame(Result::ACK, $context->getResult());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnProcessorExceptionExtensionMethodWithExpectedContext()
    {
        $exception = new \LogicException('Exception exception');

        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException($exception)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->never())
            ->method('onResult')
        ;
        $extension
            ->expects($this->once())
            ->method('onProcessorException')
            ->with($this->isInstanceOf(ProcessorException::class))
            ->willReturnCallback(function (ProcessorException $context) use ($contextStub, $expectedMessage, $exception) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertSame($exception, $context->getException());
                $this->assertGreaterThan(1, $context->getReceivedAt());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getResult());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Exception exception');
        $queueConsumer->consume();
    }

    public function testShouldContinueConsumptionIfResultSetOnProcessorExceptionExtension()
    {
        $result = Result::ack();

        $expectedMessage = $this->createMessageMock();

        $subscriptionConsumerMock = new DummySubscriptionConsumer();
        $subscriptionConsumerMock->addMessage($expectedMessage, 'foo_queue');

        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException(new \LogicException())
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onProcessorException')
            ->willReturnCallback(function (ProcessorException $context) use ($result) {
                $context->setResult($result);
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onResult')
            ->willReturnCallback(function (MessageResult $context) use ($result) {
                $this->assertSame($result, $context->getResult());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnPostMessageReceivedExtensionMethodWithExpectedContext()
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
            ->method('onPostMessageReceived')
            ->with($this->isInstanceOf(PostMessageReceived::class))
            ->willReturnCallback(function (PostMessageReceived $context) use (
                $contextStub,
                $expectedMessage
            ) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
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

    public function testShouldAllowInterruptConsumingOnPostConsume()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPostConsume')
            ->with($this->isInstanceOf(PostConsume::class))
            ->willReturnCallback(function (PostConsume $context) {
                $context->interruptExecution();
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onEnd')
            ->with($this->isInstanceOf(End::class))
            ->willReturnCallback(function (End $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertGreaterThan(1, $context->getStartTime());
                $this->assertGreaterThan(1, $context->getEndTime());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldSetMainExceptionAsPreviousToExceptionThrownOnProcessorException()
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
            ->method('onProcessorException')
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

    public function testShouldAllowInterruptConsumingOnPostMessageReceived()
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
            ->method('onPostMessageReceived')
            ->with($this->isInstanceOf(PostMessageReceived::class))
            ->willReturnCallback(function (PostMessageReceived $context) {
                $context->interruptExecution();
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onEnd')
            ->with($this->isInstanceOf(End::class))
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldNotCallOnEndIfExceptionThrow()
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
            ->expects($this->never())
            ->method('onEnd')
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
            ->method('onInitLogger')
            ->with($this->isInstanceOf(InitLogger::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPreSubscribe')
            ->with($this->isInstanceOf(PreSubscribe::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPreConsume')
            ->with($this->isInstanceOf(PreConsume::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onMessageReceived')
            ->with($this->isInstanceOf(MessageReceived::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->isInstanceOf(MessageResult::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPostMessageReceived')
            ->with($this->isInstanceOf(PostMessageReceived::class))
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1));
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume(new ChainExtension([$runtimeExtension]));
    }

    public function testShouldChangeLoggerOnInitLogger()
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
            ->method('onInitLogger')
            ->with($this->isInstanceOf(InitLogger::class))
            ->willReturnCallback(function (InitLogger $context) use ($expectedLogger) {
                $context->changeLogger($expectedLogger);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onPreSubscribe')
            ->with($this->isInstanceOf(PreSubscribe::class))
            ->willReturnCallback(function (PreSubscribe $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onPreConsume')
            ->with($this->isInstanceOf(PreConsume::class))
            ->willReturnCallback(function (PreConsume $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onMessageReceived')
            ->with($this->isInstanceOf(MessageReceived::class))
            ->willReturnCallback(function (MessageReceived $context) use ($expectedLogger) {
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

        /** @var MessageReceived[] $actualContexts */
        $actualContexts = [];

        $extension = $this->createExtension();
        $extension
            ->expects($this->any())
            ->method('onMessageReceived')
            ->with($this->isInstanceOf(MessageReceived::class))
            ->willReturnCallback(function (MessageReceived $context) use (&$actualContexts) {
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

        $this->assertSame($firstMessage, $actualContexts[0]->getMessage());
        $this->assertSame($secondMessage, $actualContexts[1]->getMessage());
        $this->assertSame($thirdMessage, $actualContexts[2]->getMessage());

        $this->assertSame($fooConsumerStub, $actualContexts[0]->getConsumer());
        $this->assertSame($barConsumerStub, $actualContexts[1]->getConsumer());
        $this->assertSame($fooConsumerStub, $actualContexts[2]->getConsumer());
    }

    public function testCaptureExitStatus()
    {
        $testExitCode = 5;

        $stubExtension = $this->createExtension();

        $stubExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Start::class))
            ->willReturnCallback(function (Start $context) use ($testExitCode) {
                $context->interruptExecution($testExitCode);
            })
        ;

        $exitExtension = new ExitStatusExtension();

        $consumer = new QueueConsumer($this->createContextStub(), $stubExtension);
        $consumer->consume(new ChainExtension([$exitExtension]));

        $this->assertEquals($testExitCode, $exitExtension->getExitStatus());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @return \PHPUnit\Framework\MockObject\MockObject|InteropContext
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

        return $context;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Processor
     */
    private function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Processor
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
     * @return \PHPUnit\Framework\MockObject\MockObject|Message
     */
    private function createMessageMock(): Message
    {
        return $this->createMock(Message::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExtensionInterface
     */
    private function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @param mixed|null $queue
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Consumer
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
     * @return SubscriptionConsumer|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }
}
