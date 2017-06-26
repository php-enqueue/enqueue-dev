<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
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

    public function testCouldBeConstructedWithInternalMessageProducer()
    {
        new TraceableProducer($this->createProducerMock());
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

        $this->assertSame([
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
    }

    public function testShouldCollectInfoIfArrayGivenAsEventMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', ['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame([
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

        $this->assertSame([
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

        $this->assertSame([
            [
                'topic' => Config::COMMAND_TOPIC,
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
    }

    public function testShouldCollectInfoIfArrayGivenAsCommandMessage()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', ['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame([
            [
                'topic' => Config::COMMAND_TOPIC,
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

        $this->assertSame([
            [
                'topic' => Config::COMMAND_TOPIC,
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

        $this->assertArraySubset([
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');
        $producer->sendEvent('aBarTopic', 'aBarBody');

        $this->assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialTopic()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');
        $producer->sendEvent('aBarTopic', 'aBarBody');

        $this->assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $producer->getTopicTraces('aFooTopic'));

        $this->assertArraySubset([
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $producer->getTopicTraces('aBarTopic'));
    }

    public function testShouldAllowGetInfoSentToSameCommand()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aFooCommand', 'aFooBody');

        $this->assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentCommands()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aBarCommand', 'aBarBody');

        $this->assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
            ['command' => 'aBarCommand', 'body' => 'aBarBody'],
        ], $producer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialCommand()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendCommand('aFooCommand', 'aFooBody');
        $producer->sendCommand('aBarCommand', 'aBarBody');

        $this->assertArraySubset([
            ['command' => 'aFooCommand', 'body' => 'aFooBody'],
        ], $producer->getCommandTraces('aFooCommand'));

        $this->assertArraySubset([
            ['command' => 'aBarCommand', 'body' => 'aBarBody'],
        ], $producer->getCommandTraces('aBarCommand'));
    }

    public function testShouldAllowClearStoredTraces()
    {
        $producer = new TraceableProducer($this->createProducerMock());

        $producer->sendEvent('aFooTopic', 'aFooBody');

        //guard
        $this->assertNotEmpty($producer->getTraces());

        $producer->clearTraces();
        $this->assertSame([], $producer->getTraces());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
