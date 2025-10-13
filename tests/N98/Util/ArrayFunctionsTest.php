<?php

declare(strict_types=1);

namespace N98\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class ArrayFunctionsTest
 *
 * @covers N98\Util\ArrayFunctions
 */
final class ArrayFunctionsTest extends TestCase
{
    /**
     * @dataProvider mergeArraysProvider
     * @param array<int|string, array<int|string, int|string>|string> $a
     * @param array<int|string, array<string, int>|string> $b
     * @param array<int|string, array<int|string, int|string>|string> $expected
     */
    public function testMergeArrays(array $a, array $b, array $expected)
    {
        $this->assertEquals($expected, ArrayFunctions::mergeArrays($a, $b));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public function mergeArraysProvider(): \Iterator
    {
        yield [[], ['Foo', 'Bar'], ['Foo', 'Bar']];
        yield [['Foo', 'Bar'], [], ['Foo', 'Bar']];
        yield [['Foo'], ['Bar'], ['Foo', 'Bar']];
        yield [['Foo', ['Bar']], ['Bar'], ['Foo', ['Bar'], 'Bar']];
        /**
         * Override Bar->Bar
         */
        yield [['Foo', 'Bar' => ['Bar' => 1]], ['Bar' => ['Bar' => 2]], ['Foo', 'Bar' => ['Bar' => 2]]];
    }

    public function testColumnOrderArrayTable()
    {
        $headers = ['foo', 'bar', 'baz'];
        $table = [['foo' => 'A1', 'baz' => 'C1', 'B1', 'D1'], ['A2', 'B2', 'C2', 'D2'], [null, null, null, 'foo' => 'A3']];

        $actual = ArrayFunctions::columnOrderArrayTable($headers, $table);
        $this->assertIsArray($actual);
        $this->assertCount(count($table), $actual);
        $expected = [['foo' => 'A1', 'bar' => 'B1', 'baz' => 'C1', 'D1'], ['foo' => 'A2', 'bar' => 'B2', 'baz' => 'C2', 'D2'], ['foo' => 'A3', 'bar' => null, 'baz' => null, null]];
        $this->assertEquals($expected, $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider provideColumnOrderings
     */
    public function testColumnOrder($columns, $array, $expected)
    {
        $actual = ArrayFunctions::columnOrder($columns, $array);
        $this->assertIsArray($actual);
        $this->assertEquals($expected, $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @see columnOrder
     * @return \Iterator<(int | string), mixed>
     */
    public function provideColumnOrderings(): \Iterator
    {
        yield [['foo', 'bar', 'baz'], ['A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C']];
        yield [['foo', 'bar', 'baz'], ['A', 'B', 'C', 'D'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D']];
        yield [['foo', 'bar', 'baz'], ['A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C']];
        yield [['foo', 'bar', 'baz'], ['buz' => 'D', 'A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'buz' => 'D']];
        yield [['foo', 'bar', 'baz'], ['foo' => 'A', 'baz' => 'C', 'B', 'D'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D']];
        yield [['foo', 'bar', 'baz'], ['foo' => 'A', 'baz' => 'C'], ['foo' => 'A', 'bar' => null, 'baz' => 'C']];
    }

    /**
     * @see matrixFilterByValue
     * @see matrixFilterStartsWith
     * @return \Iterator<(int | string), mixed>
     */
    public function provideMatrix(): \Iterator
    {
        yield [[['foo' => 'bar'], ['foo' => 'baz'], ['foo' => 'zaz']]];
    }

    /**
     * @dataProvider provideMatrix
     * @param array<int, array<string, string>> $matrix
     */
    public function testMatrixFilterByValue(array $matrix)
    {
        $this->assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterByValue($matrix, 'foo', 'bar');
        $this->assertCount(1, $filtered);
    }

    /**
     * @dataProvider provideMatrix
     * @param array<int, array<string, string>> $matrix
     */
    public function testMatrixFilterStartsWith(array $matrix)
    {
        $this->assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterStartswith($matrix, 'foo', 'ba');
        $this->assertCount(2, $filtered);
    }
}
