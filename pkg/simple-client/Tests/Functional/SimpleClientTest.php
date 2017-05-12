<?php
namespace Enqueue\SimpleClient\Tests\Functional;

use Enqueue\SimpleClient\SimpleClient;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrMessage;
use Enqueue\Test\RabbitmqAmqpExtension;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;
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
        $amqp = [
            'transport' => [
                'amqp' => [
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                    'user' => getenv('SYMFONY__RABBITMQ__USER'),
                    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                ],
            ],
        ];

        $rabbitmqAmqp = [
            'transport' => [
                'rabbitmq_amqp' => [
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                    'user' => getenv('SYMFONY__RABBITMQ__USER'),
                    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                ],
            ],
        ];

        return [[$amqp, $rabbitmqAmqp]];
    }

    /**
     * @dataProvider transportConfigDataProvider
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
