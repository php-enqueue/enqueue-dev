<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Bundle\Tests\Functional\App\CustomAppKernel;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @group functional
 */
class UseCasesTest extends WebTestCase
{
    public function setUp()
    {
        // do not call parent::setUp.
        // parent::setUp();
    }

    public function provideEnqueueConfigs()
    {
        yield 'amqp' => [[
            'transport' => [
                'default' => 'amqp',
                'amqp' => [
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
                    'user' => getenv('SYMFONY__RABBITMQ__USER'),
                    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                    'lazy' => false,
                ],
            ],
        ]];

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

        // Symfony 2.x does not such env syntax
        if (version_compare(Kernel::VERSION, '3.2', '>=')) {
            yield 'default_dsn_as_env' => [[
                'transport' => [
                    'default' => '%env(AMQP_DSN)%',
                ],
            ]];
        }

        yield 'default_dbal_as_dsn' => [[
            'transport' => [
                'default' => getenv('DOCTINE_DSN'),
            ],
        ]];

        yield 'stomp' => [[
            'transport' => [
                'default' => 'stomp',
                'stomp' => [
                    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
                    'port' => getenv('SYMFONY__RABBITMQ__STOMP__PORT'),
                    'login' => getenv('SYMFONY__RABBITMQ__USER'),
                    'password' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
                    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
                    'lazy' => false,
                ],
            ],
        ]];

        yield 'predis' => [[
            'transport' => [
                'default' => 'redis',
                'redis' => [
                    'host' => getenv('SYMFONY__REDIS__HOST'),
                    'port' => (int) getenv('SYMFONY__REDIS__PORT'),
                    'vendor' => 'predis',
                    'lazy' => false,
                ],
            ],
        ]];

        yield 'phpredis' => [[
            'transport' => [
                'default' => 'redis',
                'redis' => [
                    'host' => getenv('SYMFONY__REDIS__HOST'),
                    'port' => (int) getenv('SYMFONY__REDIS__PORT'),
                    'vendor' => 'phpredis',
                    'lazy' => false,
                ],
            ],
        ]];

        yield 'fs' => [[
            'transport' => [
                'default' => 'fs',
                'fs' => [
                    'path' => sys_get_temp_dir(),
                ],
            ],
        ]];

        yield 'fs_dsn' => [[
            'transport' => [
                'default' => 'fs',
                'fs' => 'file://'.sys_get_temp_dir(),
            ],
        ]];

        yield 'default_fs_as_dsn' => [[
            'transport' => [
                'default' => 'file://'.sys_get_temp_dir(),
            ],
        ]];

        yield 'dbal' => [[
            'transport' => [
                'default' => 'dbal',
                'dbal' => [
                    'connection' => [
                        'dbname' => getenv('SYMFONY__DB__NAME'),
                        'user' => getenv('SYMFONY__DB__USER'),
                        'password' => getenv('SYMFONY__DB__PASSWORD'),
                        'host' => getenv('SYMFONY__DB__HOST'),
                        'port' => getenv('SYMFONY__DB__PORT'),
                        'driver' => getenv('SYMFONY__DB__DRIVER'),
                    ],
                ],
            ],
        ]];

        yield 'dbal_dsn' => [[
            'transport' => [
                'default' => 'dbal',
                'dbal' => getenv('DOCTINE_DSN'),
            ],
        ]];

        yield 'sqs' => [[
            'transport' => [
                'default' => 'sqs',
                'sqs' => [
                    'key' => getenv('AWS__SQS__KEY'),
                    'secret' => getenv('AWS__SQS__SECRET'),
                    'region' => getenv('AWS__SQS__REGION'),
                ],
            ],
        ]];
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testProducerSendsMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $queue = $this->getPsrContext()->createQueue('enqueue.test');

        $consumer = $this->getPsrContext()->createConsumer($queue);

        $message = $consumer->receive(100);

        $this->assertInstanceOf(PsrMessage::class, $message);
        $this->assertSame('test message body', $message->getBody());
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testClientConsumeMessagesFromExplicitlySetQueue(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $command = $this->container->get('enqueue.client.consume_messages_command');
        $processor = $this->container->get('test.message.processor');

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 2,
            '--time-limit' => 'now +10 seconds',
            'client-queue-names' => ['test'],
        ]);

        $this->assertInstanceOf(PsrMessage::class, $processor->message);
        $this->assertEquals('test message body', $processor->message->getBody());
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testTransportConsumeMessagesCommandShouldConsumeMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $command = $this->container->get('enqueue.command.consume_messages');
        $command->setContainer($this->container);
        $processor = $this->container->get('test.message.processor');

        $this->getMessageProducer()->send(TestProcessor::TOPIC, 'test message body');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 1,
            '--time-limit' => '+10sec',
            '--queue' => ['enqueue.test'],
            'processor-service' => 'test.message.processor',
        ]);

        $this->assertInstanceOf(PsrMessage::class, $processor->message);
        $this->assertEquals('test message body', $processor->message->getBody());
    }

    /**
     * @return string
     */
    public static function getKernelClass()
    {
        include_once __DIR__.'/App/CustomAppKernel.php';

        return CustomAppKernel::class;
    }

    protected function customSetUp(array $enqueueConfig)
    {
        static::$class = null;

        $this->client = static::createClient(['enqueue_config' => $enqueueConfig]);
        $this->client->getKernel()->boot();
        static::$kernel = $this->client->getKernel();
        $this->container = static::$kernel->getContainer();

        /** @var DriverInterface $driver */
        $driver = $this->container->get('enqueue.client.driver');
        $context = $this->getPsrContext();

        $queue = $driver->createQueue('test');

        //guard
        $this->assertEquals('enqueue.test', $queue->getQueueName());

        if (method_exists($context, 'deleteQueue')) {
            $context->deleteQueue($queue);
        }

        $driver->setupBroker();
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        /** @var CustomAppKernel $kernel */
        $kernel = parent::createKernel($options);

        $kernel->setEnqueueConfig(isset($options['enqueue_config']) ? $options['enqueue_config'] : []);

        return $kernel;
    }

    /**
     * @return ProducerInterface|object
     */
    private function getMessageProducer()
    {
        return $this->container->get('enqueue.client.producer');
    }

    /**
     * @return PsrContext|object
     */
    private function getPsrContext()
    {
        return $this->container->get('enqueue.transport.context');
    }
}
