<?php

namespace Enqueue\SimpleClient\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\PsrMessage;
use Interop\Queue\PurgeQueueNotSupportedException;
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
        ]];

        yield 'dbal_dsn' => [[
            'transport' => getenv('DOCTRINE_DSN'),
        ]];

        yield 'rabbitmq_stomp' => [[
            'transport' => [
                'dsn' => getenv('RABITMQ_STOMP_DSN'),
                'lazy' => false,
                'management_plugin_installed' => true,
            ],
        ]];

        yield 'predis_dsn' => [[
            'transport' => [
                'dsn' => getenv('PREDIS_DSN'),
                'lazy' => false,
            ],
        ]];

        yield 'fs_dsn' => [[
            'transport' => 'file://'.sys_get_temp_dir(),
        ]];

        yield 'sqs' => [[
            'transport' => [
                'dsn' => getenv('SQS_DSN'),
            ],
        ]];

        yield 'mongodb_dsn' => [[
            'transport' => getenv('MONGO_DSN'),
        ]];
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testProduceAndConsumeOneMessage(array $config)
    {
        $actualMessage = null;

        $config['client'] = [
            'prefix' => str_replace('.', '', uniqid('enqueue', true)),
            'app_name' => 'simple_client',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_processor_queue' => 'test',
        ];

        $client = new SimpleClient($config);

        $client->bind('foo_topic', 'foo_processor', function (PsrMessage $message) use (&$actualMessage) {
            $actualMessage = $message;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendEvent('foo_topic', 'Hello there!');

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+30sec')),
            new LimitConsumedMessagesExtension(2),
        ]));

        $this->assertInstanceOf(PsrMessage::class, $actualMessage);
        $this->assertSame('Hello there!', $actualMessage->getBody());
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testProduceAndRouteToTwoConsumes($config)
    {
        $received = 0;

        $config['client'] = [
            'prefix' => str_replace('.', '', uniqid('enqueue', true)),
            'app_name' => 'simple_client',
            'router_topic' => 'test',
            'router_queue' => 'test',
            'default_processor_queue' => 'test',
        ];

        $client = new SimpleClient($config);

        $client->bind('foo_topic', 'foo_processor1', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });
        $client->bind('foo_topic', 'foo_processor2', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });

        $client->setupBroker();
        $this->purgeQueue($client);

        $client->sendEvent('foo_topic', 'Hello there!');

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+2sec')),
            new LimitConsumedMessagesExtension(3),
        ]));

        $this->assertSame(2, $received);
    }

    protected function purgeQueue(SimpleClient $client): void
    {
        $driver = $client->getDriver();

        $queue = $driver->createQueue($driver->getConfig()->getDefaultProcessorQueueName());

        try {
            $client->getContext()->purgeQueue($queue);
        } catch (PurgeQueueNotSupportedException $e) {
        }
    }
}
