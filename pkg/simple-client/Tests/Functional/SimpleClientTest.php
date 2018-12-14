<?php

namespace Enqueue\SimpleClient\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class SimpleClientTest extends TestCase
{
    public function transportConfigDataProvider()
    {
        yield 'amqp_dsn' => [[
            'transport' => getenv('AMQP_DSN'),
        ], '+1sec'];

        yield 'dbal_dsn' => [[
            'transport' => getenv('DOCTRINE_DSN'),
        ], '+1sec'];

        yield 'rabbitmq_stomp' => [[
            'transport' => [
                'dsn' => getenv('RABITMQ_STOMP_DSN'),
                'lazy' => false,
                'management_plugin_installed' => true,
            ],
        ], '+1sec'];

        yield 'predis_dsn' => [[
            'transport' => [
                'dsn' => getenv('PREDIS_DSN'),
                'lazy' => false,
            ],
        ], '+1sec'];

        yield 'fs_dsn' => [[
            'transport' => 'file://'.sys_get_temp_dir(),
        ], '+1sec'];

        yield 'sqs' => [[
            'transport' => [
                'dsn' => getenv('SQS_DSN'),
            ],
        ], '+1sec'];

        yield 'mongodb_dsn' => [[
            'transport' => getenv('MONGO_DSN'),
        ], '+1sec'];
    }

    public function testShouldWorkWithStringDsnConstructorArgument()
    {
        $actualMessage = null;

        $client = new SimpleClient(getenv('AMQP_DSN'));

        $client->bindTopic('foo_topic', function (Message $message) use (&$actualMessage) {
            $actualMessage = $message;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendEvent('foo_topic', 'Hello there!');

        $client->getQueueConsumer()->setReceiveTimeout(200);
        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+1sec')),
            new LimitConsumedMessagesExtension(2),
        ]));

        $this->assertInstanceOf(Message::class, $actualMessage);
        $this->assertSame('Hello there!', $actualMessage->getBody());
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testSendEventWithOneSubscriber($config, string $timeLimit)
    {
        $actualMessage = null;

        $config['client'] = [
            'prefix' => str_replace('.', '', uniqid('enqueue', true)),
            'app_name' => 'simple_client',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_queue' => 'test',
        ];

        $client = new SimpleClient($config);

        $client->bindTopic('foo_topic', function (Message $message) use (&$actualMessage) {
            $actualMessage = $message;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendEvent('foo_topic', 'Hello there!');

        $client->getQueueConsumer()->setReceiveTimeout(200);
        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime($timeLimit)),
            new LimitConsumedMessagesExtension(2),
        ]));

        $this->assertInstanceOf(Message::class, $actualMessage);
        $this->assertSame('Hello there!', $actualMessage->getBody());
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testSendEventWithTwoSubscriber($config, string $timeLimit)
    {
        $received = 0;

        $config['client'] = [
            'prefix' => str_replace('.', '', uniqid('enqueue', true)),
            'app_name' => 'simple_client',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_queue' => 'test',
        ];

        $client = new SimpleClient($config);

        $client->bindTopic('foo_topic', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });
        $client->bindTopic('foo_topic', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendEvent('foo_topic', 'Hello there!');
        $client->getQueueConsumer()->setReceiveTimeout(200);
        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime($timeLimit)),
            new LimitConsumedMessagesExtension(3),
        ]));

        $this->assertSame(2, $received);
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testSendCommand($config, string $timeLimit)
    {
        $received = 0;

        $config['client'] = [
            'prefix' => str_replace('.', '', uniqid('enqueue', true)),
            'app_name' => 'simple_client',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_queue' => 'test',
        ];

        $client = new SimpleClient($config);

        $client->bindCommand('foo_command', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendCommand('foo_command', 'Hello there!');
        $client->getQueueConsumer()->setReceiveTimeout(200);
        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime($timeLimit)),
            new LimitConsumedMessagesExtension(1),
        ]));

        $this->assertSame(1, $received);
    }

    protected function purgeQueue(SimpleClient $client): void
    {
        $driver = $client->getDriver();

        $queue = $driver->createQueue($driver->getConfig()->getDefaultQueue());

        try {
            $client->getDriver()->getContext()->purgeQueue($queue);
        } catch (PurgeQueueNotSupportedException $e) {
        }
    }
}
