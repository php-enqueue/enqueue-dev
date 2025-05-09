<?php

namespace Enqueue\Tests\Client;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class TraceableProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(ProducerInterface::class, TraceableProducer::class);
    }

    public function testShouldPassAllArgumentsToInternalEventMessageProducerSendMethod()
    {
        $topic = 'theTopic';
        $body = 'theBody';

        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('sendEvent')
            ->with($topic, $body)
        ;

        $producer = new TraceableProducer($internalMessageProducer);

        $producer->sendEvent($topic, $body);
    }

    public function testShouldCollectInfoIfStringGivenAsEventMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');

        Assert::assertArraySubset([
            [
                'topic' => 'aFooTopic',
                'command' => null,
                'body' => 'aFooBody',
                'headers' => [],
                'properties' => [],
                'priority' => null,
                'expire' => null,
                'delay' => null,
                'timestamp' => null,
                'contentType' => null,
                'messageId' => null,
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldCollectInfoIfArrayGivenAsEventMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', ['foo' => 'fooVal', 'bar' => 'barVal']);

        Assert::assertArraySubset([
            [
                'topic' => 'aFooTopic',
                'command' => null,
                'body' => ['foo' => 'fooVal', 'bar' => 'barVal'],
                'headers' => [],
                'properties' => [],
                'priority' => null,
                'expire' => null,
                'delay' => null,
                'timestamp' => null,
                'contentType' => null,
                'messageId' => null,
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldCollectInfoIfEventMessageObjectGivenAsMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $message = new Message();
        $message->setBody(['foo' => 'fooVal', 'bar' => 'barVal']);
        $message->setProperty('fooProp', 'fooVal');
        $message->setHeader('fooHeader', 'fooVal');
        $message->setContentType('theContentType');
        $message->setDelay('theDelay');
        $message->setExpire('theExpire');
        $message->setMessageId('theMessageId');
        $message->setPriority('theMessagePriority');
        $message->setTimestamp('theTimestamp');

        $producer->sendEvent('aFooTopic', $message);

        Assert::assertArraySubset([
            [
                'topic' => 'aFooTopic',
                'command' => null,
                'body' => ['foo' => 'fooVal', 'bar' => 'barVal'],
                'headers' => ['fooHeader' => 'fooVal'],
                'properties' => ['fooProp' => 'fooVal'],
                'priority' => 'theMessagePriority',
                'expire' => 'theExpire',
                'delay' => 'theDelay',
                'timestamp' => 'theTimestamp',
                'contentType' => 'theContentType',
                'messageId' => 'theMessageId',
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldNotStoreAnythingIfInternalEventMessageProducerThrowsException()
    {
        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception())
        ;

        $producer = new TraceableProducer($internalMessageProducer);

        $this->expectException(\Exception::class);

        try {
            $producer->sendEvent('aFooTopic', 'aFooBody');
        } finally {
            $this->assertEmpty($producer->getTraces());
        }
    }

    public function testShouldPassAllArgumentsToInternalCommandMessageProducerSendMethod()
    {
        $command = 'theCommand';
        $body = 'theBody';

        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('sendCommand')
            ->with($command, $body)
        ;

        $producer = new TraceableProducer($internalMessageProducer);

        $producer->sendCommand($command, $body);
    }

    public function testShouldCollectInfoIfStringGivenAsCommandMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');

        Assert::assertArraySubset([
            [
                'topic' => null,
                'command' => 'aFooCommand',
                'body' => 'aFooBody',
                'headers' => [],
                'properties' => [],
                'priority' => null,
                'expire' => null,
                'delay' => null,
                'timestamp' => null,
                'contentType' => null,
                'messageId' => null,
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldCollectInfoIfArrayGivenAsCommandMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', ['foo' => 'fooVal', 'bar' => 'barVal']);

        Assert::assertArraySubset([
            [
                'topic' => null,
                'command' => 'aFooCommand',
                'body' => ['foo' => 'fooVal', 'bar' => 'barVal'],
                'headers' => [],
                'properties' => [],
                'priority' => null,
                'expire' => null,
                'delay' => null,
                'timestamp' => null,
                'contentType' => null,
                'messageId' => null,
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldCollectInfoIfCommandMessageObjectGivenAsMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $message = new Message();
        $message->setBody(['foo' => 'fooVal', 'bar' => 'barVal']);
        $message->setProperty('fooProp', 'fooVal');
        $message->setHeader('fooHeader', 'fooVal');
        $message->setContentType('theContentType');
        $message->setDelay('theDelay');
        $message->setExpire('theExpire');
        $message->setMessageId('theMessageId');
        $message->setPriority('theMessagePriority');
        $message->setTimestamp('theTimestamp');

        $producer->sendCommand('aFooCommand', $message);

        Assert::assertArraySubset([
            [
                'topic' => null,
                'command' => 'aFooCommand',
                'body' => ['foo' => 'fooVal', 'bar' => 'barVal'],
                'headers' => ['fooHeader' => 'fooVal'],
                'properties' => ['fooProp' => 'fooVal'],
                'priority' => 'theMessagePriority',
                'expire' => 'theExpire',
                'delay' => 'theDelay',
                'timestamp' => 'theTimestamp',
                'contentType' => 'theContentType',
                'messageId' => 'theMessageId',
            ],
        ], $producer->getTraces());

        $this->assertArrayHasKey('sentAt', $producer->getTraces()[0]);
    }

    public function testShouldNotStoreAnythingIfInternalCommandMessageProducerThrowsException()
    {
        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('sendCommand')
            ->willThrowException(new \Exception())
        ;

        $producer = new TraceableProducer($internalMessageProducer);

        $this->expectException(\Exception::class);

        try {
            $producer->sendCommand('aFooCommand', 'aFooBody');
        } finally {
            $this->assertEmpty($producer->getTraces());
        }
    }

    public function testShouldAllowGetInfoSentToSameTopic()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');
        $producer->sendEvent('aFooTopic', 'aFooBody');

        Assert::assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');
        $producer->sendEvent('aBarTopic', 'aBarBody');

        Assert::assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialTopic()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');
        $producer->sendEvent('aBarTopic', 'aBarBody');

        Assert::assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $producer->getTopicTraces('aFooTopic'));

        Assert::assertArraySubset([
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $producer->getTopicTraces('aBarTopic'));
    }

    public function testShouldAllowGetInfoSentToSameCommand()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aFooCommand', 'aFooBody');

        Assert::assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentCommands()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aBarCommand', 'aBarBody');

        Assert::assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
            ['command' => 'aBarCommand', 'body' => 'aBarBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialCommand()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aBarCommand', 'aBarBody');

        Assert::assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
        ], $producer->getCommandTraces('aFooCommand'));

        Assert::assertArraySubset([
            ['command' => 'aBarCommand', 'body' => 'aBarBody'],
        ], $producer->getCommandTraces('aBarCommand'));
    }

    public function testShouldAllowClearStoredTraces()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');

        // guard
        $this->assertNotEmpty($producer->getTraces());

        $producer->clearTraces();
        $this->assertSame([], $producer->getTraces());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
