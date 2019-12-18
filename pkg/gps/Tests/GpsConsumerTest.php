<?php

namespace Enqueue\Gps\Tests;

use Enqueue\Gps\GpsConsumer;
use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsMessage;
use Enqueue\Gps\GpsQueue;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use PHPUnit\Framework\TestCase;

class GpsConsumerTest extends TestCase
{
    public function testAcknowledgeShouldThrowExceptionIfNativeMessageNotSet()
    {
        $consumer = new GpsConsumer($this->createContextMock(), new GpsQueue(''));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Native google pub/sub message required but it is empty');

        $consumer->acknowledge(new GpsMessage(''));
    }

    public function testShouldAcknowledgeMessage()
    {
        $nativeMessage = new Message([], []);

        $subscription = $this->createSubscriptionMock();
        $subscription
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($nativeMessage))
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscription')
            ->willReturn($subscription)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $consumer = new GpsConsumer($context, new GpsQueue('queue-name'));

        $message = new GpsMessage('');
        $message->setNativeMessage($nativeMessage);

        $consumer->acknowledge($message);
    }

    public function testRejectShouldThrowExceptionIfNativeMessageNotSet()
    {
        $consumer = new GpsConsumer($this->createContextMock(), new GpsQueue(''));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Native google pub/sub message required but it is empty');

        $consumer->acknowledge(new GpsMessage(''));
    }

    public function testShouldRejectMessage()
    {
        $nativeMessage = new Message([], []);

        $subscription = $this->createSubscriptionMock();
        $subscription
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($nativeMessage))
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscription')
            ->willReturn($subscription)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $consumer = new GpsConsumer($context, new GpsQueue('queue-name'));

        $message = new GpsMessage('');
        $message->setNativeMessage($nativeMessage);

        $consumer->reject($message);
    }

    public function testShouldReceiveMessageNoWait()
    {
        $message = new GpsMessage('the body');
        $nativeMessage = new Message([
            'data' => json_encode($message),
        ], []);

        $subscription = $this->createSubscriptionMock();
        $subscription
            ->expects($this->once())
            ->method('pull')
            ->with($this->identicalTo([
                'maxMessages' => 1,
                'returnImmediately' => true,
            ]))
            ->willReturn([$nativeMessage])
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscription')
            ->willReturn($subscription)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $consumer = new GpsConsumer($context, new GpsQueue('queue-name'));

        $message = $consumer->receiveNoWait();

        $this->assertInstanceOf(GpsMessage::class, $message);
        $this->assertSame('the body', $message->getBody());
    }

    public function testShouldReceiveMessage()
    {
        $message = new GpsMessage('the body');
        $nativeMessage = new Message([
            'data' => json_encode($message),
        ], []);

        $subscription = $this->createSubscriptionMock();
        $subscription
            ->expects($this->once())
            ->method('pull')
            ->with($this->identicalTo([
                'maxMessages' => 1,
                'requestTimeout' => 12.345,
            ]))
            ->willReturn([$nativeMessage])
        ;

        $client = $this->createPubSubClientMock();
        $client
            ->expects($this->once())
            ->method('subscription')
            ->willReturn($subscription)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $consumer = new GpsConsumer($context, new GpsQueue('queue-name'));

        $message = $consumer->receive(12345);

        $this->assertInstanceOf(GpsMessage::class, $message);
        $this->assertSame('the body', $message->getBody());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|GpsContext
     */
    private function createContextMock()
    {
        return $this->createMock(GpsContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PubSubClient
     */
    private function createPubSubClientMock()
    {
        return $this->createMock(PubSubClient::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Subscription
     */
    private function createSubscriptionMock()
    {
        return $this->createMock(Subscription::class);
    }
}
