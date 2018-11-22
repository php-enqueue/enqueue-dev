<?php

namespace Enqueue\Wamp\Tests\Functional;

use Enqueue\Test\WampExtension;
use Enqueue\Wamp\WampMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Thruway\Logging\Logger;

/**
 * @group functional
 * @group Wamp
 */
class WampSubscriptionConsumerTest extends TestCase
{
    use WampExtension;

    public static function setUpBeforeClass()
    {
        Logger::set(new NullLogger());
    }

    public function testShouldSendAndReceiveMessage()
    {
        $context = $this->buildWampContext();
        $topic = $context->createTopic('topic');
        $consumer = $context->createSubscriptionConsumer();
        $producer = $context->createProducer();
        $message = $context->createMessage('the body');

        $receivedMessage = null;
        $consumer->subscribe($context->createConsumer($topic), function ($message) use (&$receivedMessage) {
            $receivedMessage = $message;

            return false;
        });

        // init client
        $consumer->consume(1);

        $consumer->getClient()->getLoop()->futureTick(function () use ($producer, $topic, $message) {
            $producer->send($topic, $message);
        });

        $consumer->consume(100);

        $this->assertInstanceOf(WampMessage::class, $receivedMessage);
        $this->assertSame('the body', $receivedMessage->getBody());
    }
}
