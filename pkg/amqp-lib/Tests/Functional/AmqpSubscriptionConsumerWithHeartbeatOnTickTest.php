<?php

namespace Enqueue\AmqpLib\Tests\Functional;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class AmqpSubscriptionConsumerWithHeartbeatOnTickTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    protected function tearDown()
    {
        if ($this->context) {
            $this->context->close();
        }

        parent::tearDown();
    }

    public function test()
    {
        $this->context = $context = $this->createContext();

        $fooQueue = $this->createQueue($context, 'foo_subscription_consumer_consume_from_all_subscribed_queues_spec');

        $expectedFooBody = 'fooBody';

        $context->createProducer()->send($fooQueue, $context->createMessage($expectedFooBody));

        $fooConsumer = $context->createConsumer($fooQueue);

        $actualBodies = [];
        $actualQueues = [];
        $callback = function (Message $message, Consumer $consumer) use (&$actualBodies, &$actualQueues) {
            declare(ticks=1) {
                $actualBodies[] = $message->getBody();
                $actualQueues[] = $consumer->getQueue()->getQueueName();

                $consumer->acknowledge($message);

                return true;
            }
        };

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($fooConsumer, $callback);

        $subscriptionConsumer->consume(1000);

        $this->assertCount(1, $actualBodies);
    }

    protected function createContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory(getenv('AMQP_DSN'));

        $context = $factory->createContext();
        $context->setQos(0, 5, false);

        return $context;
    }

    protected function createQueue(AmqpContext $context, string $queueName): AmqpQueue
    {
        $queue = $context->createQueue($queueName);
        $context->declareQueue($queue);
        $context->purgeQueue($queue);

        return $queue;
    }
}
