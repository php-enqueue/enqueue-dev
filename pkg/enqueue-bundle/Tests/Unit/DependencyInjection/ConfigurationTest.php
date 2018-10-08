<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection;

use Enqueue\Bundle\DependencyInjection\Configuration;
use Enqueue\Client\RouterProcessor;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        $this->assertClassImplements(ConfigurationInterface::class, Configuration::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(Configuration::class);
    }

    public function testCouldBeConstructedWithDebugAsArgument()
    {
        new Configuration(true);
    }

    public function testShouldUseDefaultConfigurationIfNothingIsConfiguredAtAll()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[]]);

        $this->assertEquals([
            'transport' => ['dsn' => 'null:'],
            'consumption' => [
                'receive_timeout' => 10000,
            ],
            'job' => false,
            'async_events' => ['enabled' => false],
            'async_commands' => ['enabled' => false],
            'extensions' => [
                'doctrine_ping_connection_extension' => false,
                'doctrine_clear_identity_map_extension' => false,
                'signal_extension' => function_exists('pcntl_signal_dispatch'),
                'reply_extension' => true,
            ],
        ], $config);
    }

    public function testShouldUseDefaultTransportIfIfTransportIsConfiguredAtAll()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => null,
        ]]);

        $this->assertEquals([
            'transport' => ['dsn' => 'null:'],
            'consumption' => [
                'receive_timeout' => 10000,
            ],
            'job' => false,
            'async_events' => ['enabled' => false],
            'async_commands' => ['enabled' => false],
            'extensions' => [
                'doctrine_ping_connection_extension' => false,
                'doctrine_clear_identity_map_extension' => false,
                'signal_extension' => function_exists('pcntl_signal_dispatch'),
                'reply_extension' => true,
            ],
        ], $config);
    }

    public function testShouldSetDefaultConfigurationForClient()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => 'null:',
            'client' => null,
        ]]);

        $this->assertArraySubset([
            'transport' => ['dsn' => 'null:'],
            'client' => [
                'prefix' => 'enqueue',
                'app_name' => 'app',
                'router_processor' => RouterProcessor::class,
                'router_topic' => 'default',
                'router_queue' => 'default',
                'default_processor_queue' => 'default',
                'traceable_producer' => true,
                'redelivered_delay_time' => 0,
            ],
        ], $config);
    }

    public function testThrowExceptionIfRouterTopicIsEmpty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.client.router_topic" cannot contain an empty value, but got "".');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => ['dsn' => 'null:'],
            'client' => [
                'router_topic' => '',
            ],
        ]]);
    }

    public function testThrowExceptionIfRouterQueueIsEmpty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.client.router_queue" cannot contain an empty value, but got "".');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => ['dsn' => 'null:'],
            'client' => [
                'router_queue' => '',
            ],
        ]]);
    }

    public function testShouldThrowExceptionIfDefaultProcessorQueueIsEmpty()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.client.default_processor_queue" cannot contain an empty value, but got "".');
        $processor->processConfiguration($configuration, [[
            'transport' => ['dsn' => 'null:'],
            'client' => [
                'default_processor_queue' => '',
            ],
        ]]);
    }

    public function testJobShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'job' => false,
        ], $config);
    }

    public function testCouldEnableJob()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'job' => true,
        ]]);

        $this->assertArraySubset([
            'job' => true,
        ], $config);
    }

    public function testDoctrinePingConnectionExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'doctrine_ping_connection_extension' => false,
            ],
        ], $config);
    }

    public function testDoctrinePingConnectionExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'extensions' => [
                'doctrine_ping_connection_extension' => true,
            ],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'doctrine_ping_connection_extension' => true,
            ],
        ], $config);
    }

    public function testDoctrineClearIdentityMapExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'doctrine_clear_identity_map_extension' => false,
            ],
        ], $config);
    }

    public function testDoctrineClearIdentityMapExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'extensions' => [
                'doctrine_clear_identity_map_extension' => true,
            ],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'doctrine_clear_identity_map_extension' => true,
            ],
        ], $config);
    }

    public function testSignalExtensionShouldBeEnabledIfPcntlExtensionIsLoaded()
    {
        $isLoaded = function_exists('pcntl_signal_dispatch');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'signal_extension' => $isLoaded,
            ],
        ], $config);
    }

    public function testSignalExtensionCouldBeDisabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'extensions' => [
                'signal_extension' => false,
            ],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'signal_extension' => false,
            ],
        ], $config);
    }

    public function testReplyExtensionShouldBeEnabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'reply_extension' => true,
            ],
        ], $config);
    }

    public function testReplyExtensionCouldBeDisabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'extensions' => [
                'reply_extension' => false,
            ],
        ]]);

        $this->assertArraySubset([
            'extensions' => [
                'reply_extension' => false,
            ],
        ], $config);
    }

    public function testShouldDisableAsyncEventsByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'async_events' => [
                'enabled' => false,
            ],
        ], $config);
    }

    public function testShouldAllowEnableAsyncEvents()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'async_events' => true,
        ]]);

        $this->assertArraySubset([
            'async_events' => [
                'enabled' => true,
            ],
        ], $config);

        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'async_events' => [
                'enabled' => true,
            ],
        ]]);

        $this->assertArraySubset([
            'async_events' => [
                'enabled' => true,
            ],
        ], $config);
    }

    public function testShouldSetDefaultConfigurationForConsumption()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
        ]]);

        $this->assertArraySubset([
            'consumption' => [
                'receive_timeout' => 10000,
            ],
        ], $config);
    }

    public function testShouldAllowConfigureConsumption()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [],
            'consumption' => [
                'receive_timeout' => 456,
            ],
        ]]);

        $this->assertArraySubset([
            'consumption' => [
                'receive_timeout' => 456,
            ],
        ], $config);
    }
}
