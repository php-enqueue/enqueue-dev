<?php

namespace Enqueue\Dsn\Tests;

use Enqueue\Dsn\Dsn;
use Enqueue\Dsn\InvalidQueryParameterTypeException;
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

    public function testShouldUrlDecodedPath()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/%2f');

        $this->assertSame('//', $dsn->getPath());
    }

    public function testShouldParseQuery()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath?foo=fooVal&bar=bar%2fVal');

        $this->assertSame('foo=fooVal&bar=bar%2fVal', $dsn->getQueryString());
        $this->assertSame(['foo' => 'fooVal', 'bar' => 'bar/Val'], $dsn->getQuery());
    }

    public function testShouldParseQueryShouldPreservePlusSymbol()
    {
        $dsn = new Dsn('amqp+ext://theUser:thePass@theHost:1267/thePath?foo=fooVal&bar=bar+Val');

        $this->assertSame('foo=fooVal&bar=bar+Val', $dsn->getQueryString());
        $this->assertSame(['foo' => 'fooVal', 'bar' => 'bar+Val'], $dsn->getQuery());
    }

    /**
     * @dataProvider provideIntQueryParameters
     */
    public function testShouldParseQueryParameterAsInt(string $parameter, int $expected)
    {
        $dsn = new Dsn('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getInt('aName'));
    }

    /**
     * @dataProvider provideOctalQueryParameters
     */
    public function testShouldParseQueryParameterAsOctalInt(string $parameter, int $expected)
    {
        $dsn = new Dsn('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getOctal('aName'));
    }

    public function testShouldReturnDefaultIntIfNotSet()
    {
        $dsn = new Dsn('foo:');

        $this->assertNull($dsn->getInt('aName'));
        $this->assertSame(123, $dsn->getInt('aName', 123));
    }

    public function testThrowIfQueryParameterNotInt()
    {
        $dsn = new Dsn('foo:?aName=notInt');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "integer"');
        $dsn->getInt('aName');
    }

    /**
     * @dataProvider provideFloatQueryParameters
     */
    public function testShouldParseQueryParameterAsFloat(string $parameter, float $expected)
    {
        $dsn = new Dsn('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getFloat('aName'));
    }

    public function testShouldReturnDefaultFloatIfNotSet()
    {
        $dsn = new Dsn('foo:');

        $this->assertNull($dsn->getFloat('aName'));
        $this->assertSame(123., $dsn->getFloat('aName', 123.));
    }

    public function testThrowIfQueryParameterNotFloat()
    {
        $dsn = new Dsn('foo:?aName=notFloat');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "float"');
        $dsn->getFloat('aName');
    }

    /**
     * @dataProvider provideBooleanQueryParameters
     */
    public function testShouldParseQueryParameterAsBoolean(string $parameter, bool $expected)
    {
        $dsn = new Dsn('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getBool('aName'));
    }

    public function testShouldReturnDefaultBoolIfNotSet()
    {
        $dsn = new Dsn('foo:');

        $this->assertNull($dsn->getBool('aName'));
        $this->assertTrue($dsn->getBool('aName', true));
    }

    public function testThrowIfQueryParameterNotBool()
    {
        $dsn = new Dsn('foo:?aName=notBool');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "bool"');
        $dsn->getBool('aName');
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

    public static function provideIntQueryParameters()
    {
        yield ['123', 123];

        yield ['+123', 123];

        yield ['-123', -123];

        yield ['010', 10];
    }

    public static function provideOctalQueryParameters()
    {
        yield ['010', 8];
    }

    public static function provideFloatQueryParameters()
    {
        yield ['123', 123.];

        yield ['+123', 123.];

        yield ['-123', -123.];

        yield ['0', 0.];
    }

    public static function provideBooleanQueryParameters()
    {
        yield ['', false];

        yield ['1', true];

        yield ['0', false];

        yield ['true', true];

        yield ['false', false];
    }
}
