<?php

namespace Enqueue\Dsn\Tests;

use Enqueue\Dsn\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function testCouldBeConstructedWithDsnAsFirstArgument()
    {
        new Dsn('foo://localhost:1234');
    }

    public function testThrowsIfSchemePartIsMissing()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. It does not have scheme separator ":".');
        new Dsn('foobar');
    }

    public function testThrowsIfSchemeContainsIllegalSymbols()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. Scheme contains illegal symbols.');
        new Dsn('foo_&%&^bar://localhost');
    }

    /**
     * @dataProvider provideSchemes
     */
    public function testShouldParseSchemeCorrectly(string $dsn, string $expectedScheme, string $expectedSchemeProtocol, array $expectedSchemeExtensions)
    {
        $dsn = new Dsn($dsn);

        $this->assertSame($expectedScheme, $dsn->getScheme());
        $this->assertSame($expectedSchemeProtocol, $dsn->getSchemeProtocol());
        $this->assertSame($expectedSchemeExtensions, $dsn->getSchemeExtensions());
    }

    public function testShouldParseUser()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('theUser', $dsn->getUser());
    }

    public function testShouldParsePassword()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('thePass', $dsn->getPassword());
    }

    public function testShouldParseHost()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('theHost', $dsn->getHost());
    }

    public function testShouldParsePort()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame(1267, $dsn->getPort());
    }

    public function testShouldParsePath()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('/thePath', $dsn->getPath());
    }

    public function testShouldParseQuery()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath?foo=fooVal&bar=bar%2fVal');

        $this->assertSame('foo=fooVal&bar=bar%2fVal', $dsn->getQueryString());
        $this->assertSame(['foo' => 'fooVal', 'bar' => 'bar/Val'], $dsn->getQuery());
    }

    public static function provideSchemes()
    {
        yield [':', '', '', []];

        yield ['FOO:', 'foo', 'foo', []];

        yield ['foo:', 'foo', 'foo', []];

        yield ['foo+bar:', 'foo+bar', 'foo', ['bar']];

        yield ['foo+bar+baz:', 'foo+bar+baz', 'foo', ['bar', 'baz']];

        yield ['foo:?bar=barVal', 'foo', 'foo', []];

        yield ['amqp+ext://guest:guest@localhost:5672/%2f', 'amqp+ext', 'amqp', ['ext']];

        yield ['amqp+ext+rabbitmq:', 'amqp+ext+rabbitmq', 'amqp', ['ext', 'rabbitmq']];
    }
}
