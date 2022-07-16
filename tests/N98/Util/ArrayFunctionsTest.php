<?php

namespace N98\Util;

use PHPUnit\Framework\TestCase;
/**
 * Class ArrayFunctionsTest
 *
 * @covers N98\Util\ArrayFunctions
 */
class ArrayFunctionsTest extends TestCase
{
    /**
     * @test
     * @dataProvider mergeArraysProvider
     */
    public function mergeArrays(array $a, array $b, array $expected)
    {
        self::assertEquals($expected, ArrayFunctions::mergeArrays($a, $b));
    }

    /**
     * @return array
     */
    public function mergeArraysProvider()
    {
        return [
            [[], ['Foo', 'Bar'], ['Foo', 'Bar']],
            [['Foo', 'Bar'], [], ['Foo', 'Bar']],
            [['Foo'], ['Bar'], ['Foo', 'Bar']],
            [['Foo', ['Bar']], ['Bar'], ['Foo', ['Bar'], 'Bar']],
            /**
             * Override Bar->Bar
             */
            [['Foo', 'Bar' => ['Bar' => 1]], ['Bar' => ['Bar' => 2]], ['Foo', 'Bar' => ['Bar' => 2]]],
        ];
    }

    /**
     * @test
     */
    public function columnOrderArrayTable()
    {
        $headers = ['foo', 'bar', 'baz'];
        $table = [['foo' => 'A1', 'baz' => 'C1', 'B1', 'D1'], ['A2', 'B2', 'C2', 'D2'], [null, null, null, 'foo' => 'A3']];

        $actual = ArrayFunctions::columnOrderArrayTable($headers, $table);
        self::assertIsArray($actual);
        self::assertCount(count($table), $actual);
        $expected = [['foo' => 'A1', 'bar' => 'B1', 'baz' => 'C1', 'D1'], ['foo' => 'A2', 'bar' => 'B2', 'baz' => 'C2', 'D2'], ['foo' => 'A3', 'bar' => null, 'baz' => null, null]];
        self::assertEquals($expected, $actual);
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     * @dataProvider provideColumnOrderings
     */
    public function columnOrder($columns, $array, $expected)
    {
        $actual = ArrayFunctions::columnOrder($columns, $array);
        self::assertIsArray($actual);
        self::assertEquals($expected, $actual);
        self::assertSame($expected, $actual);
    }

    /**
     * @see columnOrder
     * @return array
     */
    public function provideColumnOrderings()
    {
        return [[['foo', 'bar', 'baz'], ['A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C']], [['foo', 'bar', 'baz'], ['A', 'B', 'C', 'D'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D']], [['foo', 'bar', 'baz'], ['A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C']], [['foo', 'bar', 'baz'], ['buz' => 'D', 'A', 'B', 'C'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'buz' => 'D']], [['foo', 'bar', 'baz'], ['foo' => 'A', 'baz' => 'C', 'B', 'D'], ['foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D']], [['foo', 'bar', 'baz'], ['foo' => 'A', 'baz' => 'C'], ['foo' => 'A', 'bar' => null, 'baz' => 'C']]];
    }

    /**
     * @see matrixFilterByValue
     * @see matrixFilterStartsWith
     * @return array
     */
    public function provideMatrix()
    {
        return [[[['foo' => 'bar'], ['foo' => 'baz'], ['foo' => 'zaz']]]];
    }

    /**
     * @test
     * @dataProvider provideMatrix
     */
    public function matrixFilterByValue(array $matrix)
    {
        self::assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterByValue($matrix, 'foo', 'bar');
        self::assertCount(1, $filtered);
    }

    /**
     * @test
     * @dataProvider provideMatrix
     */
    public function matrixFilterStartsWith(array $matrix)
    {
        self::assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterStartswith($matrix, 'foo', 'ba');
        self::assertCount(2, $filtered);
    }
}
