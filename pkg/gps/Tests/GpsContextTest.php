<?php

namespace Enqueue\Gps\Tests;

use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Google\Cloud\Core\Exception\ConflictException;
use Google\Cloud\PubSub\PubSubClient;
use Interop\Queue\Exception\InvalidDestinationException;
use PHPUnit\Framework\TestCase;

class GpsContextTest extends TestCase
{
    public function testShouldDeclareTopic()
    {
        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('createTopic')
            ->with('topic-name')
        ;

        $topic = new GpsTopic('topic-name');

        $context = new GpsContext($client);
        $context->declareTopic($topic);
    }

    public function testDeclareTopicShouldCatchConflictException()
    {
        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('createTopic')
            ->willThrowException(new ConflictException(''))
        ;

        $topic = new GpsTopic('');

        $context = new GpsContext($client);
        $context->declareTopic($topic);
    }

    public function testShouldSubscribeTopicToQueue()
    {
        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscribe')
            ->with('queue-name', 'topic-name', $this->identicalTo(['ackDeadlineSeconds' => 10]))
        ;

        $topic = new GpsTopic('topic-name');
        $queue = new GpsQueue('queue-name');

        $context = new GpsContext($client);

        $context->subscribe($topic, $queue);
    }

    public function testSubscribeShouldCatchConflictException()
    {
        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscribe')
            ->willThrowException(new ConflictException(''))
        ;

        $topic = new GpsTopic('topic-name');
        $queue = new GpsQueue('queue-name');

        $context = new GpsContext($client);

        $context->subscribe($topic, $queue);
    }

    public function testCreateConsumerShouldThrowExceptionIfInvalidDestination()
    {
        $context = new GpsContext($this->createPubSubClientMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Gps\GpsQueue but got Enqueue\Gps\GpsTopic');

        $context->createConsumer(new GpsTopic(''));
    }

    /**
     * @return PubSubClient|\PHPUnit\Framework\MockObject\MockObject|PubSubClient
     */
    private function createPubSubClientMock()
    {
        return $this->createMock(PubSubClient::class);
    }
}
