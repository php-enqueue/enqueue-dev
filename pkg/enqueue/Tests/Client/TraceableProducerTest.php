<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Test\ClassExtensionTrait;

class TraceableProducerTest extends \PHPUnit_Framework_TestCase
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

    public function testShouldPassAllArgumentsToInternalMessageProducerSendMethod()
    {
        $topic = 'theTopic';
        $body = 'theBody';

        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->with($topic, $body)
        ;

        $messageProducer = new TraceableProducer($internalMessageProducer);

        $messageProducer->send($topic, $body);
    }

    public function testShouldCollectInfoIfStringGivenAsMessage()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', 'aFooBody');

        $this->assertSame([
            [
                'topic' => 'aFooTopic',
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
        ], $messageProducer->getTraces());
    }

    public function testShouldCollectInfoIfArrayGivenAsMessage()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', ['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame([
            [
                'topic' => 'aFooTopic',
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
        ], $messageProducer->getTraces());
    }

    public function testShouldCollectInfoIfMessageObjectGivenAsMessage()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

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

        $messageProducer->send('aFooTopic', $message);

        $this->assertSame([
            [
                'topic' => 'aFooTopic',
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
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSameTopic()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aFooTopic', 'aFooBody');

        $this->assertArraySubset([
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aBarTopic', 'aBarBody');

        $this->assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialTopicTopics()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aBarTopic', 'aBarBody');

        $this->assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $messageProducer->getTopicTraces('aFooTopic'));

        $this->assertArraySubset([
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $messageProducer->getTopicTraces('aBarTopic'));
    }

    public function testShouldNotStoreAnythingIfInternalMessageProducerThrowsException()
    {
        $internalMessageProducer = $this->createProducerMock();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception())
        ;

        $messageProducer = new TraceableProducer($internalMessageProducer);

        $this->expectException(\Exception::class);

        try {
            $messageProducer->send('aFooTopic', 'aFooBody');
        } finally {
            $this->assertEmpty($messageProducer->getTraces());
        }
    }

    public function testShouldAllowClearStoredTraces()
    {
        $messageProducer = new TraceableProducer($this->createProducerMock());

        $messageProducer->send('aFooTopic', 'aFooBody');

        //guard
        $this->assertNotEmpty($messageProducer->getTraces());

        $messageProducer->clearTraces();
        $this->assertSame([], $messageProducer->getTraces());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
