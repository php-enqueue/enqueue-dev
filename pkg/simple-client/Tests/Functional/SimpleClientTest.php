<?php

namespace Enqueue\SimpleClient\Tests\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\SimpleClient\SimpleClient;
use Enqueue\Test\RabbitmqAmqpExtension;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class SimpleClientTest extends TestCase
{
    use RabbitmqAmqpExtension;
    use RabbitmqManagmentExtensionTrait;

    public function setUp()
    {
        if (false == getenv('SYMFONY__RABBITMQ__HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $this->removeQueue('enqueue.app.default');
    }

    public function transportConfigDataProvider()
    {
        yield 'amqp' => [[
            'transport' => [
                'default' => 'amqp',
                'amqp' => [
                    'driver' => 'ext',
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                    'user' => getenv('SYMFONY__RABBITMQ__USER'),
                    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                ],
            ],
        ]];

        yield 'config_as_dsn_string' => [getenv('AMQP_DSN')];

        yield 'amqp_dsn' => [[
            'transport' => [
                'default' => 'amqp',
                'amqp' => getenv('AMQP_DSN'),
            ],
        ]];

        yield 'default_amqp_as_dsn' => [[
            'transport' => [
                'default' => getenv('AMQP_DSN'),
            ],
        ]];

        yield [[
            'transport' => [
                'default' => 'rabbitmq_amqp',
                'rabbitmq_amqp' => [
                    'driver' => 'ext',
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                    'user' => getenv('SYMFONY__RABBITMQ__USER'),
                    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                ],
            ],
        ]];
    }

    /**
     * @dataProvider transportConfigDataProvider
     *
     * @param mixed $config
     */
    public function testProduceAndConsumeOneMessage($config)
    {
        $actualMessage = null;

        $client = new SimpleClient($config);
        $client->bind('foo_topic', 'foo_processor', function (PsrMessage $message) use (&$actualMessage) {
            $actualMessage = $message;

            return Result::ACK;
        });

        $client->send('foo_topic', 'Hello there!', true);

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5sec')),
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

        $client = new SimpleClient($config);
        $client->bind('foo_topic', 'foo_processor1', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });
        $client->bind('foo_topic', 'foo_processor2', function () use (&$received) {
            ++$received;

            return Result::ACK;
        });

        $client->send('foo_topic', 'Hello there!', true);

        $client->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5sec')),
            new LimitConsumedMessagesExtension(3),
        ]));

        $this->assertSame(2, $received);
    }
}
