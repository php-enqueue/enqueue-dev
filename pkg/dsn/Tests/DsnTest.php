<?php

namespace Enqueue\Dsn\Tests;

use Enqueue\Dsn\Dsn;
use Enqueue\Dsn\InvalidQueryParameterTypeException;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function testThrowsIfSchemePartIsMissing()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. It does not have scheme separator ":".');
        Dsn::parseFirst('foobar');
    }

    public function testThrowsIfSchemeContainsIllegalSymbols()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. Scheme contains illegal symbols.');
        Dsn::parseFirst('foo_&%&^bar://localhost');
    }

    /**
     * @dataProvider provideSchemes
     */
    public function testShouldParseSchemeCorrectly(string $dsn, string $expectedScheme, string $expectedSchemeProtocol, array $expectedSchemeExtensions)
    {
        $dsn = Dsn::parseFirst($dsn);

        $this->assertSame($expectedScheme, $dsn->getScheme());
        $this->assertSame($expectedSchemeProtocol, $dsn->getSchemeProtocol());
        $this->assertSame($expectedSchemeExtensions, $dsn->getSchemeExtensions());
    }

    public function testShouldParseUser()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('theUser', $dsn->getUser());
    }

    public function testShouldParsePassword()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('thePass', $dsn->getPassword());
    }

    public function testShouldParseHost()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('theHost', $dsn->getHost());
    }

    public function testShouldParsePort()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame(1267, $dsn->getPort());
    }

    public function testShouldParsePath()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath');

        $this->assertSame('/thePath', $dsn->getPath());
    }

    public function testShouldUrlDecodedPath()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/%2f');

        $this->assertSame('//', $dsn->getPath());
    }

    public function testShouldParseQuery()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath?foo=fooVal&bar=bar%2fVal');

        $this->assertSame('foo=fooVal&bar=bar%2fVal', $dsn->getQueryString());
        $this->assertSame(['foo' => 'fooVal', 'bar' => 'bar/Val'], $dsn->getQuery());
    }

    public function testShouldParseQueryShouldPreservePlusSymbol()
    {
        $dsn = Dsn::parseFirst('amqp+ext://theUser:thePass@theHost:1267/thePath?foo=fooVal&bar=bar+Val');

        $this->assertSame('foo=fooVal&bar=bar+Val', $dsn->getQueryString());
        $this->assertSame(['foo' => 'fooVal', 'bar' => 'bar+Val'], $dsn->getQuery());
    }

    /**
     * @dataProvider provideIntQueryParameters
     */
    public function testShouldParseQueryParameterAsInt(string $parameter, int $expected)
    {
        $dsn = Dsn::parseFirst('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getDecimal('aName'));
    }

    /**
     * @dataProvider provideOctalQueryParameters
     */
    public function testShouldParseQueryParameterAsOctalInt(string $parameter, int $expected)
    {
        $dsn = Dsn::parseFirst('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getOctal('aName'));
    }

    public function testShouldReturnDefaultIntIfNotSet()
    {
        $dsn = Dsn::parseFirst('foo:');

        $this->assertNull($dsn->getDecimal('aName'));
        $this->assertSame(123, $dsn->getDecimal('aName', 123));
    }

    public function testThrowIfQueryParameterNotDecimal()
    {
        $dsn = Dsn::parseFirst('foo:?aName=notInt');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "decimal"');
        $dsn->getDecimal('aName');
    }

    public function testThrowIfQueryParameterNotOctalButString()
    {
        $dsn = Dsn::parseFirst('foo:?aName=notInt');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "octal"');
        $dsn->getOctal('aName');
    }

    public function testThrowIfQueryParameterNotOctalButDecimal()
    {
        $dsn = Dsn::parseFirst('foo:?aName=123');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "octal"');
        $dsn->getOctal('aName');
    }

    public function testThrowIfQueryParameterInvalidOctal()
    {
        $dsn = Dsn::parseFirst('foo:?aName=0128');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "octal"');
        $dsn->getOctal('aName');
    }

    public function testThrowIfQueryParameterInvalidArray()
    {
        $dsn = Dsn::parseFirst('foo:?aName=foo');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "array"');
        $dsn->getArray('aName');
    }

    /**
     * @dataProvider provideFloatQueryParameters
     */
    public function testShouldParseQueryParameterAsFloat(string $parameter, float $expected)
    {
        $dsn = Dsn::parseFirst('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getFloat('aName'));
    }

    public function testShouldParseDSNWithoutAuthorityPart()
    {
        $dsn = Dsn::parseFirst('foo:///foo');

        $this->assertNull($dsn->getUser());
        $this->assertNull($dsn->getPassword());
        $this->assertNull($dsn->getHost());
        $this->assertNull($dsn->getPort());
    }

    public function testShouldReturnDefaultFloatIfNotSet()
    {
        $dsn = Dsn::parseFirst('foo:');

        $this->assertNull($dsn->getFloat('aName'));
        $this->assertSame(123., $dsn->getFloat('aName', 123.));
    }

    public function testThrowIfQueryParameterNotFloat()
    {
        $dsn = Dsn::parseFirst('foo:?aName=notFloat');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "float"');
        $dsn->getFloat('aName');
    }

    /**
     * @dataProvider provideBooleanQueryParameters
     */
    public function testShouldParseQueryParameterAsBoolean(string $parameter, bool $expected)
    {
        $dsn = Dsn::parseFirst('foo:?aName='.$parameter);

        $this->assertSame($expected, $dsn->getBool('aName'));
    }

    /**
     * @dataProvider provideArrayQueryParameters
     */
    public function testShouldParseQueryParameterAsArray(string $query, array $expected)
    {
        $dsn = Dsn::parseFirst('foo:?'.$query);

        $this->assertSame($expected, $dsn->getArray('aName')->toArray());
    }

    public function testShouldReturnDefaultBoolIfNotSet()
    {
        $dsn = Dsn::parseFirst('foo:');

        $this->assertNull($dsn->getBool('aName'));
        $this->assertTrue($dsn->getBool('aName', true));
    }

    public function testThrowIfQueryParameterNotBool()
    {
        $dsn = Dsn::parseFirst('foo:?aName=notBool');

        $this->expectException(InvalidQueryParameterTypeException::class);
        $this->expectExceptionMessage('The query parameter "aName" has invalid type. It must be "bool"');
        $dsn->getBool('aName');
    }

    public function testShouldParseMultipleDsnsWithUsernameAndPassword()
    {
        $dsns = Dsn::parse('foo://user:pass@foo,bar');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('user', $dsns[0]->getUser());
        $this->assertSame('pass', $dsns[0]->getPassword());
        $this->assertSame('foo', $dsns[0]->getHost());

        $this->assertSame('user', $dsns[1]->getUser());
        $this->assertSame('pass', $dsns[1]->getPassword());
        $this->assertSame('bar', $dsns[1]->getHost());
    }

    public function testShouldParseMultipleDsnsWithPorts()
    {
        $dsns = Dsn::parse('foo://foo:123,bar:567');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertSame(123, $dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertSame(567, $dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsWhenFirstHasPort()
    {
        $dsns = Dsn::parse('foo://foo:123,bar');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertSame(123, $dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertNull($dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsWhenLastHasPort()
    {
        $dsns = Dsn::parse('foo://foo,bar:567');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertNull($dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertSame(567, $dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsWithPath()
    {
        $dsns = Dsn::parse('foo://foo:123,bar:567/foo/bar');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertSame(123, $dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertSame(567, $dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsWithQuery()
    {
        $dsns = Dsn::parse('foo://foo:123,bar:567?foo=val');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertSame(123, $dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertSame(567, $dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsWithQueryAndPath()
    {
        $dsns = Dsn::parse('foo://foo:123,bar:567/foo?foo=val');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $this->assertSame('foo', $dsns[0]->getHost());
        $this->assertSame(123, $dsns[0]->getPort());

        $this->assertSame('bar', $dsns[1]->getHost());
        $this->assertSame(567, $dsns[1]->getPort());
    }

    public function testShouldParseMultipleDsnsIfOnlyColonProvided()
    {
        $dsns = Dsn::parse(':');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(1, $dsns);

        $this->assertNull($dsns[0]->getHost());
        $this->assertNull($dsns[0]->getPort());
    }

    public function testShouldParseMultipleDsnsWithOnlyScheme()
    {
        $dsns = Dsn::parse('foo:');

        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(1, $dsns);

        $this->assertSame('foo', $dsns[0]->getScheme());
        $this->assertNull($dsns[0]->getHost());
        $this->assertNull($dsns[0]->getPort());
    }

    public function testShouldParseExpectedNumberOfMultipleDsns()
    {
        $dsns = Dsn::parse('foo://foo');
        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(1, $dsns);

        $dsns = Dsn::parse('foo://foo,bar');
        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(2, $dsns);

        $dsns = Dsn::parse('foo://foo,bar,baz');
        $this->assertContainsOnly(Dsn::class, $dsns);
        $this->assertCount(3, $dsns);
    }

    public function testShouldParseDsnWithOnlyUser()
    {
        $dsn = Dsn::parseFirst('foo://user@host');

        $this->assertSame('user', $dsn->getUser());
        $this->assertNull($dsn->getPassword());
        $this->assertSame('foo', $dsn->getScheme());
        $this->assertSame('host', $dsn->getHost());
    }

    public function testShouldUrlEncodeUser()
    {
        $dsn = Dsn::parseFirst('foo://us%3Aer@host');

        $this->assertSame('us:er', $dsn->getUser());
        $this->assertNull($dsn->getPassword());
        $this->assertSame('foo', $dsn->getScheme());
        $this->assertSame('host', $dsn->getHost());
    }

    public function testShouldUrlEncodePassword()
    {
        $dsn = Dsn::parseFirst('foo://user:pass%3Aword@host');

        $this->assertSame('user', $dsn->getUser());
        $this->assertSame('pass:word', $dsn->getPassword());
        $this->assertSame('foo', $dsn->getScheme());
        $this->assertSame('host', $dsn->getHost());
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

    public static function provideArrayQueryParameters()
    {
        yield ['aName[0]=val', ['val']];

        yield ['aName[key]=val', ['key' => 'val']];

        yield ['aName[0]=fooVal&aName[1]=barVal', ['fooVal', 'barVal']];

        yield ['aName[foo]=fooVal&aName[bar]=barVal', ['foo' => 'fooVal', 'bar' => 'barVal']];
    }
}
