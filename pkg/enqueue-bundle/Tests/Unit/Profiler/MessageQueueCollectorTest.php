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

    public function testCouldBeConstructedWithEmptyConstructor()
    {
        new MessageQueueCollector();
    }

    public function testShouldReturnExpectedName()
    {
        $collector = new MessageQueueCollector();

        $this->assertEquals('enqueue.message_queue', $collector->getName());
    }

    public function testShouldReturnEmptySentMessageArrayIfNotTraceableProducer()
    {
        $collector = new MessageQueueCollector();
        $collector->addProducer('default', $this->createProducerMock());

        $collector->collect(new Request(), new Response());

        $this->assertSame([], $collector->getSentMessages());
    }

    public function testShouldReturnSentMessageArrayTakenFromTraceableProducers()
    {
        $producer1 = new TraceableProducer($this->createProducerMock());
        $producer1->sendEvent('fooTopic1', 'fooMessage');
        $producer1->sendCommand('barCommand1', 'barMessage');

        $producer2 = new TraceableProducer($this->createProducerMock());
        $producer2->sendEvent('fooTopic2', 'fooMessage');

        $collector = new MessageQueueCollector();
        $collector->addProducer('foo', $producer1);
        $collector->addProducer('bar', $producer2);

        $collector->collect(new Request(), new Response());

        $this->assertArraySubset(
            [
                'foo' => [
                    [
                        'topic' => 'fooTopic1',
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
                        'command' => 'barCommand1',
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
                'bar' => [
                    [
                        'topic' => 'fooTopic2',
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
                ],
            ],
            $collector->getSentMessages()
        );
    }

    public function testShouldPrettyPrintKnownPriority()
    {
        $collector = new MessageQueueCollector();

        $this->assertEquals('normal', $collector->prettyPrintPriority(MessagePriority::NORMAL));
    }

    public function testShouldPrettyPrintUnknownPriority()
    {
        $collector = new MessageQueueCollector();

        $this->assertEquals('unknownPriority', $collector->prettyPrintPriority('unknownPriority'));
    }

    public function testShouldEnsureStringKeepStringSame()
    {
        $collector = new MessageQueueCollector();

        $this->assertEquals('foo', $collector->ensureString('foo'));
        $this->assertEquals('bar baz', $collector->ensureString('bar baz'));
    }

    public function testShouldEnsureStringEncodeArrayToJson()
    {
        $collector = new MessageQueueCollector();

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
