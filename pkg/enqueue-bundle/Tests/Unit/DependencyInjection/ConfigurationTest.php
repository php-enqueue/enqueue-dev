<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Enqueue\Bundle\DependencyInjection\Configuration;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
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

    public function testShouldProcessSeveralTransports()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => 'default:',
            ],
            'foo' => [
                'transport' => 'foo:',
            ],
        ]]);

        $this->assertConfigEquals([
            'default' => [
                'transport' => [
                    'dsn' => 'default:',
                ],
            ],
            'foo' => [
                'transport' => [
                    'dsn' => 'foo:',
                ],
            ],
        ], $config);
    }

    public function testTransportFactoryShouldValidateEachTransportAccordingToItsRules()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both options factory_class and factory_service are set. Please choose one.');
        $processor->processConfiguration($configuration, [
            [
                'default' => [
                    'transport' => [
                        'factory_class' => 'aClass',
                        'factory_service' => 'aService',
                    ],
                ],
            ],
        ]);
    }

    public function testShouldSetDefaultConfigurationForClient()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => 'null:',
                'client' => null,
            ],
        ]]);

        $this->assertConfigEquals([
            'default' => [
                'client' => [
                    'prefix' => 'enqueue',
                    'app_name' => 'app',
                    'router_processor' => null,
                    'router_topic' => 'default',
                    'router_queue' => 'default',
                    'default_queue' => 'default',
                    'traceable_producer' => true,
                    'redelivered_delay_time' => 0,
                ],
            ],
        ], $config);
    }

    public function testThrowIfClientDriverOptionsIsNotArray()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $this->expectException(InvalidTypeException::class);
        // Exception messages vary slightly between versions
        $this->expectExceptionMessageMatches(
            '/Invalid type for path "enqueue\.default\.client\.driver_options"\. Expected "?array"?, but got "?string"?/'
        );

        $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => 'null:',
                'client' => [
                    'driver_options' => 'invalidOption',
                ],
            ],
        ]]);
    }

    public function testShouldConfigureClientDriverOptions()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => 'null:',
                'client' => [
                    'driver_options' => [
                        'foo' => 'fooVal',
                    ],
                ],
            ],
        ]]);

        $this->assertConfigEquals([
            'default' => [
                'client' => [
                    'prefix' => 'enqueue',
                    'app_name' => 'app',
                    'router_processor' => null,
                    'router_topic' => 'default',
                    'router_queue' => 'default',
                    'default_queue' => 'default',
                    'traceable_producer' => true,
                    'driver_options' => [
                        'foo' => 'fooVal',
                    ],
                ],
            ],
        ], $config);
    }

    public function testThrowExceptionIfRouterTopicIsEmpty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.default.client.router_topic" cannot contain an empty value, but got "".');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => ['dsn' => 'null:'],
                'client' => [
                    'router_topic' => '',
                ],
            ],
        ]]);
    }

    public function testThrowExceptionIfRouterQueueIsEmpty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.default.client.router_queue" cannot contain an empty value, but got "".');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => ['dsn' => 'null:'],
                'client' => [
                    'router_queue' => '',
                ],
            ],
        ]]);
    }

    public function testShouldThrowExceptionIfDefaultProcessorQueueIsEmpty()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "enqueue.default.client.default_queue" cannot contain an empty value, but got "".');
        $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => ['dsn' => 'null:'],
                'client' => [
                    'default_queue' => '',
                ],
            ],
        ]]);
    }

    public function testJobShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'job' => [
                    'enabled' => false,
                ],
            ],
        ], $config);
    }

    public function testCouldEnableJob()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'job' => true,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'job' => true,
            ],
        ], $config);
    }

    public function testDoctrinePingConnectionExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_ping_connection_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testDoctrinePingConnectionExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
                'extensions' => [
                    'doctrine_ping_connection_extension' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_ping_connection_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testDoctrineClearIdentityMapExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_clear_identity_map_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testDoctrineClearIdentityMapExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_clear_identity_map_extension' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_clear_identity_map_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testDoctrineOdmClearIdentityMapExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_odm_clear_identity_map_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testDoctrineOdmClearIdentityMapExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_odm_clear_identity_map_extension' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_odm_clear_identity_map_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testDoctrineClosedEntityManagerExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_closed_entity_manager_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testDoctrineClosedEntityManagerExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
                'extensions' => [
                    'doctrine_closed_entity_manager_extension' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'doctrine_closed_entity_manager_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testResetServicesExtensionShouldBeDisabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => null,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'reset_services_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testResetServicesExtensionCouldBeEnabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reset_services_extension' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'reset_services_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testSignalExtensionShouldBeEnabledIfPcntlExtensionIsLoaded()
    {
        $isLoaded = function_exists('pcntl_signal_dispatch');

        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'signal_extension' => $isLoaded,
                ],
            ],
        ], $config);
    }

    public function testSignalExtensionCouldBeDisabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'signal_extension' => false,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'signal_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testReplyExtensionShouldBeEnabledByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'reply_extension' => true,
                ],
            ],
        ], $config);
    }

    public function testReplyExtensionCouldBeDisabled()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reply_extension' => false,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'extensions' => [
                    'reply_extension' => false,
                ],
            ],
        ], $config);
    }

    public function testShouldDisableAsyncEventsByDefault()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'async_events' => [
                    'enabled' => false,
                ],
            ],
        ], $config);
    }

    public function testShouldAllowEnableAsyncEvents()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'async_events' => true,
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'async_events' => [
                    'enabled' => true,
                ],
            ],
        ], $config);

        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'async_events' => [
                    'enabled' => true,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'async_events' => [
                    'enabled' => true,
                ],
            ],
        ], $config);
    }

    public function testShouldSetDefaultConfigurationForConsumption()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'consumption' => [
                    'receive_timeout' => 10000,
                ],
            ],
        ], $config);
    }

    public function testShouldAllowConfigureConsumption()
    {
        $configuration = new Configuration(true);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'default' => [
                'transport' => [],
                'consumption' => [
                    'receive_timeout' => 456,
                ],
            ],
        ]]);

        Assert::assertArraySubset([
            'default' => [
                'consumption' => [
                    'receive_timeout' => 456,
                ],
            ],
        ], $config);
    }

    private function assertConfigEquals(array $expected, array $actual): void
    {
        Assert::assertArraySubset($expected, $actual, false, var_export($actual, true));
    }
}
