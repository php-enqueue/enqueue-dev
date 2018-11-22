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
            'theSeparator',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
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
            'theSeparator',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertSame('', $config->getPrefix());
    }

    public function testShouldReturnAppNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            'theApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertEquals('theApp', $config->getApp());
    }

    /**
     * @dataProvider provideEmptyStrings
     */
    public function testShouldTrimReturnAppNameSetInConstructor(string $empty)
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            $empty,
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertSame('', $config->getApp());
    }

    public function testShouldReturnRouterProcessorNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertEquals('aRouterProcessorName', $config->getRouterProcessor());
    }

    public function testShouldReturnRouterTopicNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertEquals('aRouterTopicName', $config->getRouterTopic());
    }

    public function testShouldReturnRouterQueueNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertEquals('aRouterQueueName', $config->getRouterQueue());
    }

    public function testShouldReturnDefaultQueueNameSetInConstructor()
    {
        $config = new Config(
            'aPrefix',
            'theSeparator',
            'aApp',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
        );

        $this->assertEquals('aDefaultQueueName', $config->getDefaultQueue());
    }

    public function testShouldCreateDefaultConfig()
    {
        $config = Config::create();

        $this->assertSame('default', $config->getDefaultQueue());
        $this->assertSame('router', $config->getRouterProcessor());
        $this->assertSame('default', $config->getRouterQueue());
        $this->assertSame('router', $config->getRouterTopic());
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
            '',
            $empty,
            'aRouterQueueName',
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
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
            '',
            'aRouterTopicName',
            $empty,
            'aDefaultQueueName',
            'aRouterProcessorName',
            [],
            []
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
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            $empty,
            'aRouterProcessorName',
            [],
            []
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
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueueName',
            $empty,
            [],
            []
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
