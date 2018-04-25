<?php

namespace Enqueue\Tests\Util;

use Enqueue\Util\VarExport;
use PHPUnit\Framework\TestCase;

class VarExportTest extends TestCase
{
    public function testCouldBeConstructedWithValueAsArgument()
    {
        new VarExport('aVal');
    }

    /**
     * @dataProvider provideValues
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testShouldConvertValueToStringUsingVarExportFunction($value, $expected)
    {
        $this->assertSame($expected, (string) new VarExport($value));
    }

    public function provideValues()
    {
        return [
            ['aString', "'aString'"],
            [123, '123'],
            [['foo' => 'fooVal'], "array (\n  'foo' => 'fooVal',\n)"],
        ];
    }
}
