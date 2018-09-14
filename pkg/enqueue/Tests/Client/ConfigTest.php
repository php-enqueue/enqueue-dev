<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testShouldReturnPrefixSetInConstructor()
    {
        $config = new Config(
            'thePrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('thePrefix', $config->getPrefix());
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testShouldTrimReturnPrefixSetInConstructor(string $empty)
    {
        $config = new Config(
            $empty,
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertSame('', $config->getPrefix());
    }

    public function testShouldReturnAppNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('theApp', $config->getAppName());
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testShouldTrimReturnAppNameSetInConstructor(string $empty)
    {
        $config = new Config(
            'aPrefix',
            $empty,
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertSame('', $config->getAppName());
    }

    public function testShouldReturnRouterProcessorNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aRouterProcessorName', $config->getRouterProcessorName());
    }

    public function testShouldReturnRouterTopicNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aRouterTopicName', $config->getRouterTopicName());
    }

    public function testShouldReturnRouterQueueNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aRouterQueueName', $config->getRouterQueueName());
    }

    public function testShouldReturnDefaultQueueNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aDefaultQueueName', $config->getDefaultProcessorQueueName());
    }

    public function testShouldCreateRouterTopicName()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aprefix.aname', $config->createTransportRouterTopicName('aName'));
    }

    public function testShouldCreateProcessorQueueName()
    {
        $config = new Config(
            'aPrefix',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aprefix.aapp.aname', $config->createTransportQueueName('aName'));
    }

    public function testShouldCreateProcessorQueueNameWithoutAppName()
    {
        $config = new Config(
            'aPrefix',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aprefix.aname', $config->createTransportQueueName('aName'));
    }

    public function testShouldCreateProcessorQueueNameWithoutPrefix()
    {
        $config = new Config(
            '',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aapp.aname', $config->createTransportQueueName('aName'));
    }

    public function testShouldCreateProcessorQueueNameWithoutPrefixAndAppName()
    {
        $config = new Config(
            '',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );

        $this->assertEquals('aname', $config->createTransportQueueName('aName'));
    }

    public function testShouldCreateDefaultConfig()
    {
        $config = Config::create();

        $this->assertSame('default', $config->getDefaultProcessorQueueName());
        $this->assertSame('router', $config->getRouterProcessorName());
        $this->assertSame('default', $config->getRouterQueueName());
        $this->assertSame('router', $config->getRouterTopicName());
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testThrowIfRouterTopicNameIsEmpty(string $empty)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Router topic is empty.');
        new Config(
            '',
            '',
            $empty,
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName'
        );
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testThrowIfRouterQueueNameIsEmpty(string $empty)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Router queue is empty.');
        new Config(
            '',
            '',
            'aRouterTopicName',
            $empty,
            'aDefaultQueueName',
            'aRouterProcessorName'
        );
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testThrowIfDefaultQueueNameIsEmpty(string $empty)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default processor queue name is empty.');
        new Config(
            '',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            $empty,
            'aRouterProcessorName'
        );
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testThrowIfRouterProcessorNameIsEmpty(string $empty)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Router processor name is empty.');
        new Config(
            '',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            $empty
        );
    }

    public function provideEmptyStrings()
    {
        yield [''];

        yield [' '];

        yield ['  '];

        yield ["\t"];
    }
}
