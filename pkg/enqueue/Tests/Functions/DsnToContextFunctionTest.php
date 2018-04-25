<?php

namespace Enqueue\Tests\Functions;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Fs\FsContext;
use Enqueue\Gps\GpsContext;
use Enqueue\Null\NullContext;
use Enqueue\Redis\RedisContext;
use Enqueue\Sqs\SqsContext;
use Enqueue\Stomp\StompContext;
use PHPUnit\Framework\TestCase;

class DsnToContextFunctionTest extends TestCase
{
    public function testThrowIfDsnEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme could not be parsed from DSN ""');

        \Enqueue\dsn_to_context('');
    }

    public function testThrowIfDsnMissingScheme()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme could not be parsed from DSN "dsnMissingScheme"');

        \Enqueue\dsn_to_context('dsnMissingScheme');
    }

    public function testThrowIfDsnNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme "http" is not supported. Supported "file", "amqp+ext"');

        \Enqueue\dsn_to_context('http://schemeNotSupported');
    }

    /**
     * @dataProvider provideDSNs
     *
     * @param mixed $dsn
     * @param mixed $expectedFactoryClass
     */
    public function testReturnsExpectedFactoryInstance($dsn, $expectedFactoryClass)
    {
        $factory = \Enqueue\dsn_to_context($dsn);

        $this->assertInstanceOf($expectedFactoryClass, $factory);
    }

    public static function provideDSNs()
    {
        yield ['amqp:', AmqpContext::class];

        yield ['amqp://user:pass@foo/vhost', AmqpContext::class];

        yield ['file:', FsContext::class];

        yield ['file://'.sys_get_temp_dir(), FsContext::class];

        yield ['null:', NullContext::class];

        yield ['redis:', RedisContext::class];

        yield ['stomp:', StompContext::class];

        yield ['sqs:', SqsContext::class];

        yield ['gps:', GpsContext::class];
    }
}
