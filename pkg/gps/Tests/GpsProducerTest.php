<?php

namespace Enqueue\Gps\Tests;

use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsMessage;
use Enqueue\Gps\GpsProducer;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use PHPUnit\Framework\TestCase;

class GpsProducerTest extends TestCase
{
    public function testShouldThrowExceptionIfInvalidDestination()
    {
        $producer = new GpsProducer($this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Gps\GpsQueue but got');

        $producer->send($this->createDestinationMock(), new GpsMessage(''));
    }

    public function testShouldSendMessageToQueue()
    {
        $queue = new GpsQueue('queue-name');
        $message = new GpsMessage('');

        $gtopic = $this->createGTopicMock();
        $gtopic
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo(['data' => '{"body":"","properties":[],"headers":[]}']))
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('topic')
            ->with('queue-name')
            ->willReturn($gtopic)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $producer = new GpsProducer($context);
        $producer->send($queue, $message);
    }

    public function testShouldSendMessageToTopic()
    {
        $topic = new GpsTopic('topic-name');
        $message = new GpsMessage('');

        $gtopic = $this->createGTopicMock();
        $gtopic
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo(['data' => '{"body":"","properties":[],"headers":[]}']))
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('topic')
            ->with('topic-name')
            ->willReturn($gtopic)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $producer = new GpsProducer($context);
        $producer->send($topic, $message);
    }

    /**
     * @return GpsContext|\PHPUnit\Framework\MockObject\MockObject|GpsContext
     */
    private function createContextMock()
    {
        return $this->createMock(GpsContext::class);
    }

    /**
     * @return PubSubClient|\PHPUnit\Framework\MockObject\MockObject|PubSubClient
     */
    private function createPubSubClientMock()
    {
        return $this->createMock(PubSubClient::class);
    }

    /**
     * @return Topic|\PHPUnit\Framework\MockObject\MockObject|Topic
     */
    private function createGTopicMock()
    {
        return $this->createMock(Topic::class);
    }

    /**
     * @return Destination|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDestinationMock()
    {
        return $this->createMock(Destination::class);
    }
}
