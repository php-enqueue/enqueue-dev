<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Bundle\Tests\Functional\App\CustomAppKernel;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Stomp\StompDestination;
use Interop\Queue\Context;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group functional
 */
class UseCasesTest extends WebTestCase
{
    const RECEIVE_TIMEOUT = 500;

    protected function setUp(): void
    {
        // do not call parent::setUp.
        // parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->getContext()) {
            $this->getContext()->close();
        }

        if (static::$kernel) {
            $fs = new Filesystem();
            $fs->remove(static::$kernel->getLogDir());
            $fs->remove(static::$kernel->getCacheDir());
        }

        parent::tearDown();
    }

    public function provideEnqueueConfigs()
    {
        $baseDir = realpath(__DIR__.'/../../../../');

        // guard
        $this->assertNotEmpty($baseDir);

        $certDir = $baseDir.'/var/rabbitmq_certificates';
        $this->assertDirectoryExists($certDir);

        yield 'amqp_dsn' => [[
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]];

        yield 'amqps_dsn' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('AMQPS_DSN'),
                    'ssl_verify' => false,
                    'ssl_cacert' => $certDir.'/cacert.pem',
                    'ssl_cert' => $certDir.'/cert.pem',
                    'ssl_key' => $certDir.'/key.pem',
                ],
            ],
        ]];

        yield 'dsn_as_env' => [[
            'default' => [
                'transport' => '%env(AMQP_DSN)%',
            ],
        ]];

        yield 'dbal_dsn' => [[
            'default' => [
                'transport' => getenv('DOCTRINE_DSN'),
            ],
        ]];

        yield 'rabbitmq_stomp' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('RABITMQ_STOMP_DSN'),
                    'lazy' => false,
                    'management_plugin_installed' => true,
                ],
            ],
        ]];

        yield 'predis_dsn' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('PREDIS_DSN'),
                    'lazy' => false,
                ],
            ],
        ]];

        yield 'phpredis_dsn' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('PHPREDIS_DSN'),
                    'lazy' => false,
                ],
            ],
        ]];

        yield 'fs_dsn' => [[
            'default' => [
                'transport' => 'file://'.sys_get_temp_dir(),
            ],
        ]];

        yield 'sqs' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('SQS_DSN'),
                ],
            ],
        ]];

        yield 'sqs_client' => [[
            'default' => [
                'transport' => [
                    'dsn' => 'sqs:',
                    'service' => 'test.sqs_client',
                    'factory_service' => 'test.sqs_custom_connection_factory_factory',
                ],
            ],
        ]];

        yield 'mongodb_dsn' => [[
            'default' => [
                'transport' => getenv('MONGO_DSN'),
            ],
        ]];

        yield 'doctrine' => [[
            'default' => [
                'transport' => 'doctrine://custom',
            ],
        ]];

        yield 'snsqs' => [[
            'default' => [
                'transport' => [
                    'dsn' => getenv('SNSQS_DSN'),
                ],
            ],
        ]];

//
//        yield 'gps' => [[
//            'transport' => [
//                'dsn' => getenv('GPS_DSN'),
//            ],
//        ]];
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testProducerSendsEventMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $expectedBody = __METHOD__.time();

        $this->getMessageProducer()->sendEvent(TestProcessor::TOPIC, $expectedBody);

        $consumer = $this->getContext()->createConsumer($this->getTestQueue());

        $message = $consumer->receive(self::RECEIVE_TIMEOUT);
        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    /**
     * @dataProvider provideEnqueueConfigs
     */
    public function testProducerSendsCommandMessage(array $enqueueConfig)
    {
        $this->customSetUp($enqueueConfig);

        $expectedBody = __METHOD__.time();

        $this->getMessageProducer()->sendCommand(TestCommandProcessor::COMMAND, $expectedBody);

        $consumer = $this->getContext()->createConsumer($this->getTestQueue());

        $message = $consumer->receive(self::RECEIVE_TIMEOUT);
        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($expectedBody, $message->getBody());
    }

    public function testProducerSendsEventMessageViaProduceCommand()
    {
        $this->customSetUp([
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]);

        $expectedBody = __METHOD__.time();

        $command = static::$container->get('test_enqueue.client.produce_command');
        $tester = new CommandTester($command);
        $tester->execute([
            'message' => $expectedBody,
            '--topic' => TestProcessor::TOPIC,
            '--client' => 'default',
        ]);

        $consumer = $this->getContext()->createConsumer($this->getTestQueue());

        $message = $consumer->receive(self::RECEIVE_TIMEOUT);
        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    public function testProducerSendsCommandMessageViaProduceCommand()
    {
        $this->customSetUp([
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]);

        $expectedBody = __METHOD__.time();

        $command = static::$container->get('test_enqueue.client.produce_command');
        $tester = new CommandTester($command);
        $tester->execute([
            'message' => $expectedBody,
            '--command' => TestCommandProcessor::COMMAND,
            '--client' => 'default',
        ]);

        $consumer = $this->getContext()->createConsumer($this->getTestQueue());

        $message = $consumer->receive(self::RECEIVE_TIMEOUT);
        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($expectedBody, $message->getBody());
    }

    public function testShouldSetupBroker()
    {
        $this->customSetUp([
            'default' => [
                'transport' => 'file://'.sys_get_temp_dir(),
            ],
        ]);

        $command = static::$container->get('test_enqueue.client.setup_broker_command');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame("Broker set up\n", $tester->getDisplay());
    }

    public function testClientConsumeCommandMessagesFromExplicitlySetQueue()
    {
        $this->customSetUp([
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]);

        $command = static::$container->get('test_enqueue.client.consume_command');
        $processor = static::$container->get('test.message.command_processor');

        $expectedBody = __METHOD__.time();

        $this->getMessageProducer()->sendCommand(TestCommandProcessor::COMMAND, $expectedBody);

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 2,
            '--receive-timeout' => 100,
            '--time-limit' => 'now + 2 seconds',
            'client-queue-names' => ['test'],
        ]);

        $this->assertInstanceOf(Message::class, $processor->message);
        $this->assertEquals($expectedBody, $processor->message->getBody());
    }

    public function testClientConsumeMessagesFromExplicitlySetQueue()
    {
        $this->customSetUp([
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]);

        $expectedBody = __METHOD__.time();

        $command = static::$container->get('test_enqueue.client.consume_command');
        $processor = static::$container->get('test.message.processor');

        $this->getMessageProducer()->sendEvent(TestProcessor::TOPIC, $expectedBody);

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 2,
            '--receive-timeout' => 100,
            '--time-limit' => 'now + 2 seconds',
            'client-queue-names' => ['test'],
        ]);

        $this->assertInstanceOf(Message::class, $processor->message);
        $this->assertEquals($expectedBody, $processor->message->getBody());
    }

    public function testTransportConsumeCommandShouldConsumeOneMessage()
    {
        $this->customSetUp([
            'default' => [
                'transport' => getenv('AMQP_DSN'),
            ],
        ]);

        if ($this->getTestQueue() instanceof StompDestination) {
            $this->markTestSkipped('The test fails with the exception Stomp\Exception\ErrorFrameException: Error "precondition_failed". '.
                'It happens because of the destination options are different from the one used while creating the dest. Nothing to do about it'
            );
        }

        $expectedBody = __METHOD__.time();

        $command = static::$container->get('test_enqueue.transport.consume_command');
        $processor = static::$container->get('test.message.processor');

        $this->getMessageProducer()->sendEvent(TestProcessor::TOPIC, $expectedBody);

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 1,
            '--time-limit' => '+2sec',
            '--receive-timeout' => 1000,
            'processor' => 'test.message.processor',
            'queues' => [$this->getTestQueue()->getQueueName()],
        ]);

        $this->assertInstanceOf(Message::class, $processor->message);
        $this->assertEquals($expectedBody, $processor->message->getBody());
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

        static::$client = static::createClient(['enqueue_config' => $enqueueConfig]);
        static::$client->getKernel()->boot();
        static::$kernel = static::$client->getKernel();
        static::$container = static::$kernel->getContainer();

        /** @var DriverInterface $driver */
        $driver = static::$container->get('test_enqueue.client.default.driver');
        $context = $this->getContext();

        $driver->setupBroker();

        try {
            $context->purgeQueue($this->getTestQueue());
        } catch (PurgeQueueNotSupportedException $e) {
        }
    }

    /**
     * @return Queue
     */
    protected function getTestQueue()
    {
        /** @var DriverInterface $driver */
        $driver = static::$container->get('test_enqueue.client.default.driver');

        return $driver->createQueue('test');
    }

    protected static function createKernel(array $options = []): CustomAppKernel
    {
        /** @var CustomAppKernel $kernel */
        $kernel = parent::createKernel($options);

        $kernel->setEnqueueConfig(isset($options['enqueue_config']) ? $options['enqueue_config'] : []);

        return $kernel;
    }

    private function getMessageProducer(): ProducerInterface
    {
        return static::$container->get('test_enqueue.client.default.producer');
    }

    private function getContext(): Context
    {
        return static::$container->get('test_enqueue.transport.default.context');
    }
}
