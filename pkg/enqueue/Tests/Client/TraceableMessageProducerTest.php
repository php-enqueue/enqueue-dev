<?php
namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\MessageProducerInterface;
use Enqueue\Client\TraceableMessageProducer;
use Enqueue\Test\ClassExtensionTrait;

class TraceableMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(MessageProducerInterface::class, TraceableMessageProducer::class);
    }

    public function testCouldBeConstructedWithInternalMessageProducer()
    {
        new TraceableMessageProducer($this->createMessageProducer());
    }

    public function testShouldPassAllArgumentsToInternalMessageProducerSendMethod()
    {
        $topic = 'theTopic';
        $body = 'theBody';

        $internalMessageProducer = $this->createMessageProducer();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->with($topic, $body)
        ;

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $messageProducer->send($topic, $body);
    }

    public function testShouldCollectInfoIfStringGivenAsMessage()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

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
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

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
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

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
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aFooTopic', 'aFooBody');

        $this->assertArraySubset([
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
                ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToDifferentTopics()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');
        $messageProducer->send('aBarTopic', 'aBarBody');

        $this->assertArraySubset([
            ['topic' => 'aFooTopic', 'body' => 'aFooBody'],
            ['topic' => 'aBarTopic', 'body' => 'aBarBody'],
        ], $messageProducer->getTraces());
    }

    public function testShouldAllowGetInfoSentToSpecialTopicTopics()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

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
        $internalMessageProducer = $this->createMessageProducer();
        $internalMessageProducer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception())
        ;

        $messageProducer = new TraceableMessageProducer($internalMessageProducer);

        $this->expectException(\Exception::class);

        try {
            $messageProducer->send('aFooTopic', 'aFooBody');
        } finally {
            $this->assertEmpty($messageProducer->getTraces());
        }
    }

    public function testShouldAllowClearStoredTraces()
    {
        $messageProducer = new TraceableMessageProducer($this->createMessageProducer());

        $messageProducer->send('aFooTopic', 'aFooBody');

        //guard
        $this->assertNotEmpty($messageProducer->getTraces());

        $messageProducer->clearTraces();
        $this->assertSame([], $messageProducer->getTraces());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducer()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
