<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullQueue;
use Enqueue\Tests\Consumption\Mock\BreakCycleExtension;
use Enqueue\Tests\Consumption\Mock\DummySubscriptionConsumer;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class QueueConsumerTest extends TestCase
{
    public function testCouldBeConstructedWithConnectionAndExtensionsAsArguments()
    {
        new QueueConsumer($this->createPsrContextStub(), null, 0);
    }

    public function testCouldBeConstructedWithConnectionOnly()
    {
        new QueueConsumer($this->createPsrContextStub());
    }

    public function testCouldBeConstructedWithConnectionAndSingleExtension()
    {
        new QueueConsumer($this->createPsrContextStub(), $this->createExtension());
    }

    public function testShouldSetEmptyArrayToBoundProcessorsPropertyInConstructor()
    {
        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $this->assertAttributeSame([], 'boundProcessors', $consumer);
    }

    public function testShouldAllowGetConnectionSetInConstructor()
    {
        $expectedConnection = $this->createPsrContextStub();

        $consumer = new QueueConsumer($expectedConnection, null, 0);

        $this->assertSame($expectedConnection, $consumer->getPsrContext());
    }

    public function testThrowIfQueueNameEmptyOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue name must be not empty.');
        $consumer->bind(new NullQueue(''), $processorMock);
    }

    public function testThrowIfQueueAlreadyBoundToProcessorOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $consumer->bind(new NullQueue('theQueueName'), $processorMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The queue was already bound.');
        $consumer->bind(new NullQueue('theQueueName'), $processorMock);
    }

    public function testShouldAllowBindProcessorToQueue()
    {
        $queue = new NullQueue('theQueueName');
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $consumer->bind($queue, $processorMock);

        $this->assertAttributeSame(['theQueueName' => [$queue, $processorMock]], 'boundProcessors', $consumer);
    }

    public function testThrowIfQueueNeitherInstanceOfQueueNorString()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The argument must be an instance of Interop\Queue\PsrQueue but got stdClass.');
        $consumer->bind(new \stdClass(), $processorMock);
    }

    public function testCouldSetGetIdleTimeout()
    {
        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $consumer->setIdleTimeout(123456.1);

        $this->assertSame(123456.1, $consumer->getIdleTimeout());
    }

    public function testCouldSetGetReceiveTimeout()
    {
        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

        $consumer->setReceiveTimeout(123456.1);

        $this->assertSame(123456.1, $consumer->getReceiveTimeout());
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

        $consumer = new QueueConsumer($context, null, 0);

        $consumer->bindCallback($queueName, $callback);

        $boundProcessors = $this->readAttribute($consumer, 'boundProcessors');

        $this->assertInternalType('array', $boundProcessors);
        $this->assertCount(1, $boundProcessors);
        $this->assertArrayHasKey($queueName, $boundProcessors);

        $this->assertInternalType('array', $boundProcessors[$queueName]);
        $this->assertCount(2, $boundProcessors[$queueName]);
        $this->assertSame($queue, $boundProcessors[$queueName][0]);
        $this->assertInstanceOf(CallbackProcessor::class, $boundProcessors[$queueName][1]);
    }

    public function testShouldReturnSelfOnBind()
    {
        $processorMock = $this->createProcessorMock();

        $consumer = new QueueConsumer($this->createPsrContextStub(), null, 0);

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

        $contextMock = $this->createMock(PsrContext::class);
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

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(1), 0, 12345);
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

        $queueConsumer = new QueueConsumer($contextMock, new BreakCycleExtension(5), 0);
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

        $contextStub = $this->createPsrContextStub();

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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::ACK)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(null)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::REJECT)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(Result::REQUEUE)
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn('invalidStatus')
        ;

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();
        $processorMock
            ->expects($this->never())
            ->method('process')
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnStartExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertNull($context->getPsrConsumer());
                $this->assertNull($context->getPsrProcessor());
                $this->assertNull($context->getLogger());
                $this->assertNull($context->getPsrMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertNull($context->getPsrQueue());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnIdleExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getPsrProcessor());
                $this->assertNull($context->getPsrConsumer());
                $this->assertNull($context->getPsrMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldCallOnBeforeReceiveExtensionMethod()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createPsrContextStub($consumerStub);

        $processorMock = $this->createProcessorStub();

        $queue = new NullQueue('foo_queue');

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($contextStub) {
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getPsrProcessor());
                $this->assertNull($context->getPsrConsumer());
                $this->assertNull($context->getPsrMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertNull($context->getPsrQueue());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldAllowInterruptConsumingOnIdle()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getPsrConsumer());
                $this->assertNull($context->getPsrProcessor());
                $this->assertNull($context->getPsrMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
        $queueConsumer->setFallbackSubscriptionConsumer(new DummySubscriptionConsumer());
        $queueConsumer->bind(new NullQueue('foo_queue'), $processorMock);

        $queueConsumer->consume();
    }

    public function testShouldNotCloseContextWhenConsumptionInterrupted()
    {
        $consumerStub = $this->createConsumerStub('foo_queue');

        $contextStub = $this->createPsrContextStub($consumerStub);
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
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);
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

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(Result::ACK, $context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
                $this->assertSame($contextStub, $context->getPsrContext());
                $this->assertSame($consumerStub, $context->getPsrConsumer());
                $this->assertSame($processorMock, $context->getPsrProcessor());
                $this->assertSame($expectedMessage, $context->getPsrMessage());
                $this->assertSame($expectedException, $context->getException());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getResult());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $chainExtensions = new ChainExtension([$extension, new BreakCycleExtension(1)]);
        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
            ->with($this->isInstanceOf(Context::class))
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

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(1), 0);
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

        $contextStub = $this->createPsrContextStub($consumerStub);

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
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $context->setLogger($expectedLogger);
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

        $queueConsumer = new QueueConsumer($contextStub, $chainExtensions, 0);
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
            ->willReturnCallback(function (PsrQueue $queue) use ($consumers) {
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

        $queueConsumer = new QueueConsumer($contextStub, new BreakCycleExtension(3), 0);
        $queueConsumer->setFallbackSubscriptionConsumer($subscriptionConsumerMock);
        $queueConsumer
            ->bind($queue1, $processorMock)
            ->bind($queue2, $anotherProcessorMock)
        ;

        $queueConsumer->consume(new ChainExtension([$extension]));

        $this->assertCount(3, $actualContexts);

        $this->assertSame($firstMessage, $actualContexts[0]->getPsrMessage());
        $this->assertSame($secondMessage, $actualContexts[1]->getPsrMessage());
        $this->assertSame($thirdMessage, $actualContexts[2]->getPsrMessage());

        $this->assertSame($fooConsumerStub, $actualContexts[0]->getPsrConsumer());
        $this->assertSame($barConsumerStub, $actualContexts[1]->getPsrConsumer());
        $this->assertSame($fooConsumerStub, $actualContexts[2]->getPsrConsumer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextWithoutSubscriptionConsumerMock(): PsrContext
    {
        $contextMock = $this->createMock(PsrContext::class);
        $contextMock
            ->expects($this->any())
            ->method('createSubscriptionConsumer')
            ->willThrowException(SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt())
        ;

        return $contextMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextStub(PsrConsumer $consumer = null)
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
            ->willReturnCallback(function (PsrQueue $queue) use ($consumer) {
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
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
     */
    private function createProcessorMock()
    {
        return $this->createMock(PsrProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
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
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrMessage
     */
    private function createMessageMock()
    {
        return $this->createMock(PsrMessage::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrConsumer
     */
    private function createConsumerStub($queue = null)
    {
        if (is_string($queue)) {
            $queue = new NullQueue($queue);
        }

        $consumerMock = $this->createMock(PsrConsumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $consumerMock;
    }

    /**
     * @return PsrSubscriptionConsumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSubscriptionConsumerMock()
    {
        return $this->createMock(PsrSubscriptionConsumer::class);
    }
}
