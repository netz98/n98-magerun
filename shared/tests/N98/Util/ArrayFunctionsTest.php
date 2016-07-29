<?php

namespace N98\Util;

/**
 * Class ArrayFunctionsTest
 *
 * @covers N98\Util\ArrayFunctions
 */
class ArrayFunctionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @param array $a
     * @param array $b
     * @param array $expected
     * @dataProvider mergeArraysProvider
     */
    public function mergeArrays(array $a, array $b, array $expected)
    {
        $this->assertEquals($expected, ArrayFunctions::mergeArrays($a, $b));
    }

    /**
     * @return array
     */
    public static function mergeArraysProvider()
    {
        return array(
            array(
                array(),
                array('Foo', 'Bar'),
                array('Foo', 'Bar')
            ),
            array(
                array('Foo', 'Bar'),
                array(),
                array('Foo', 'Bar')
            ),
            array(
                array('Foo'),
                array('Bar'),
                array('Foo', 'Bar')
            ),
            array(
                array('Foo', array('Bar')),
                array('Bar'),
                array('Foo', array('Bar'), 'Bar')
            ),

            /**
             * Override Bar->Bar
             */
            array(
                array('Foo', 'Bar' => array('Bar' => 1)),
                array('Bar' => array('Bar' => 2)),
                array('Foo', 'Bar' => array('Bar' => 2))
            ),
        );
    }

    /**
     * @test
     */
    public function columnOrderArrayTable()
    {
        $headers = array('foo', 'bar', 'baz');
        $table = array(
            array('foo' => 'A1', 'baz' => 'C1', 'B1', 'D1'),
            array('A2', 'B2', 'C2', 'D2'),
            array(null, null, null, 'foo' => 'A3'),
        );

        $actual = ArrayFunctions::columnOrderArrayTable($headers, $table);
        $this->assertInternalType('array', $actual);
        $this->assertCount(count($table), $actual);
        $expected = array(
            array('foo' => 'A1', 'bar' => 'B1', 'baz' => 'C1', 'D1'),
            array('foo' => 'A2', 'bar' => 'B2', 'baz' => 'C2', 'D2'),
            array('foo' => 'A3', 'bar' => null, 'baz' => null, null),
        );
        $this->assertEquals($expected, $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @dataProvider provideColumnOrderings
     */
    public function columnOrder($columns, $array, $expected)
    {
        $actual = ArrayFunctions::columnOrder($columns, $array);
        $this->assertInternalType('array', $actual);
        $this->assertEquals($expected, $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @see columnOrder
     * @return array
     */
    public function provideColumnOrderings()
    {
        return array(
            array(
                array('foo', 'bar', 'baz'),
                array('A', 'B', 'C'),
                array('foo' => 'A', 'bar' => 'B', 'baz' => 'C'),
            ),
            array(
                array('foo', 'bar', 'baz'),
                array('A', 'B', 'C', 'D'),
                array('foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D'),
            ),
            array(
                array('foo', 'bar', 'baz'),
                array('A', 'B', 'C'),
                array('foo' => 'A', 'bar' => 'B', 'baz' => 'C'),
            ),
            array(
                array('foo', 'bar', 'baz'),
                array('buz' => 'D', 'A', 'B', 'C'),
                array('foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'buz' => 'D'),
            ),
            array(
                array('foo', 'bar', 'baz'),
                array('foo' => 'A', 'baz' => 'C', 'B', 'D'),
                array('foo' => 'A', 'bar' => 'B', 'baz' => 'C', 'D'),
            ),
            array(
                array('foo', 'bar', 'baz'),
                array('foo' => 'A', 'baz' => 'C'),
                array('foo' => 'A', 'bar' => null, 'baz' => 'C'),
            ),
        );
    }
}
