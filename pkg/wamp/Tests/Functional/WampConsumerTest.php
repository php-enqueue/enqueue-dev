<?php

namespace Enqueue\Wamp\Tests\Functional;

use Enqueue\Test\RetryTrait;
use Enqueue\Test\WampExtension;
use Enqueue\Wamp\WampMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thruway\Logging\Logger;

/**
 * @group functional
 * @group Wamp
 * @retry 5
 */
class WampConsumerTest extends TestCase
{
    use RetryTrait;
    use WampExtension;

    public static function setUpBeforeClass(): void
    {
        Logger::set(new NullLogger());
    }

    public function testShouldSendAndReceiveMessage()
    {
        $context = $this->buildWampContext();
        $topic = $context->createTopic('topic');
        $consumer = $context->createConsumer($topic);
        $producer = $context->createProducer();
        $message = $context->createMessage('the body');

        // init client
        $consumer->receive(1);

        $consumer->getClient()->getLoop()->futureTick(function () use ($producer, $topic, $message) {
            $producer->send($topic, $message);
        });

        $receivedMessage = $consumer->receive(100);

        $this->assertInstanceOf(WampMessage::class, $receivedMessage);
        $this->assertSame('the body', $receivedMessage->getBody());
    }

    public function testShouldSendAndReceiveNoWaitMessage()
    {
        $context = $this->buildWampContext();
        $topic = $context->createTopic('topic');
        $consumer = $context->createConsumer($topic);
        $producer = $context->createProducer();
        $message = $context->createMessage('the body');

        // init client
        $consumer->receive(1);

        $consumer->getClient()->getLoop()->futureTick(function () use ($producer, $topic, $message) {
            $producer->send($topic, $message);
        });

        $receivedMessage = $consumer->receiveNoWait();

        $this->assertInstanceOf(WampMessage::class, $receivedMessage);
        $this->assertSame('the body', $receivedMessage->getBody());
    }
}
