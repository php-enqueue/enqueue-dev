<?php

namespace Enqueue\Bundle\Tests\Unit\Profiler;

use Enqueue\Bundle\Profiler\MessageQueueCollector;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class MessageQueueCollectorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendDataCollectorClass()
    {
        $this->assertClassExtends(DataCollector::class, MessageQueueCollector::class);
    }

    public function testCouldBeConstructedWithMessageProducerAsFirstArgument()
    {
        new MessageQueueCollector($this->createProducerMock());
    }

    public function testShouldReturnExpectedName()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $this->assertEquals('enqueue.message_queue', $collector->getName());
    }

    public function testShouldReturnEmptySentMessageArrayIfNotTraceableProducer()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $collector->collect(new Request(), new Response());

        $this->assertSame([], $collector->getSentMessages());
    }

    public function testShouldReturnSentMessageArrayTakenFromTraceableProducer()
    {
        $producer = new TraceableProducer($this->createProducerMock());
        $producer->sendEvent('fooTopic', 'fooMessage');
        $producer->sendCommand('barCommand', 'barMessage');

        $collector = new MessageQueueCollector($producer);

        $collector->collect(new Request(), new Response());

        $this->assertEquals(
            [
                [
                    'topic' => 'fooTopic',
                    'command' => null,
                    'body' => 'fooMessage',
                    'headers' => [],
                    'properties' => [],
                    'priority' => null,
                    'expire' => null,
                    'delay' => null,
                    'timestamp' => null,
                    'contentType' => null,
                    'messageId' => null,
                ],
                [
                    'topic' => null,
                    'command' => 'barCommand',
                    'body' => 'barMessage',
                    'headers' => [],
                    'properties' => [],
                    'priority' => null,
                    'expire' => null,
                    'delay' => null,
                    'timestamp' => null,
                    'contentType' => null,
                    'messageId' => null,
                ],
            ],
            $collector->getSentMessages()
        );
    }

    public function testShouldPrettyPrintKnownPriority()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $this->assertEquals('normal', $collector->prettyPrintPriority(MessagePriority::NORMAL));
    }

    public function testShouldPrettyPrintUnknownPriority()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $this->assertEquals('unknownPriority', $collector->prettyPrintPriority('unknownPriority'));
    }

    public function testShouldEnsureStringKeepStringSame()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $this->assertEquals('foo', $collector->ensureString('foo'));
        $this->assertEquals('bar baz', $collector->ensureString('bar baz'));
    }

    public function testShouldEnsureStringEncodeArrayToJson()
    {
        $collector = new MessageQueueCollector($this->createProducerMock());

        $this->assertEquals('["foo","bar"]', $collector->ensureString(['foo', 'bar']));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TraceableProducer
     */
    protected function createTraceableProducerMock()
    {
        return $this->createMock(TraceableProducer::class);
    }
}
