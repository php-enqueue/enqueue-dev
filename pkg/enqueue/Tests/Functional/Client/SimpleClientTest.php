<?php

namespace Enqueue\Tests\Functional\Client;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Client\SimpleClient;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\Psr\Message;
use Enqueue\Test\RabbitmqAmqpExtension;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;

class SimpleClientTest extends \PHPUnit_Framework_TestCase
{
    use RabbitmqAmqpExtension;
    use RabbitmqManagmentExtensionTrait;

    /**
     * @var AmqpContext
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->buildAmqpContext();

        $this->removeQueue('default');
    }

    public function testProduceAndConsumeOneMessage()
    {
        $actualMessage = null;

        $client = new SimpleClient($this->context);
        $client->bind('foo_topic', function (Message $message) use (&$actualMessage) {
            $actualMessage = $message;

            return Result::ACK;
        });

        $client->send('foo_topic', 'Hello there!');

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5sec')),
            new LimitConsumedMessagesExtension(2),
        ]));

        $this->assertInstanceOf(Message::class, $actualMessage);
        $this->assertSame('Hello there!', $actualMessage->getBody());
    }

    public function testProduceAndRouteToTwoConsumes()
    {
        $received = 0;

        $client = new SimpleClient($this->context);
        $client->bind('foo_topic', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });
        $client->bind('foo_topic', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });

        $client->send('foo_topic', 'Hello there!');

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5sec')),
            new LimitConsumedMessagesExtension(3),
        ]));

        $this->assertSame(2, $received);
    }
}
