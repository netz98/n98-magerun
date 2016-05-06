<?php

namespace N98\Util;

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
    public function mergeArraysProvider()
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
     * @return array
     * @see filterMatrixByValue
     */
    public function provideMatrix()
    {
        return array(
            array(
                array(
                    array('foo' => 'bar'),
                    array('foo' => 'baz'),
                    array('foo' => 'zaz'),
                )
            )
        );
    }

    /**
     * @param array $matrix
     * @test
     * @dataProvider provideMatrix
     */
    public function matrixFilterByValue(array $matrix)
    {
        $this->assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterByValue($matrix, 'foo', 'bar');
        $this->assertCount(1, $filtered);
    }

    /**
     * @param array $matrix
     * @test
     * @dataProvider provideMatrix
     */
    public function matrixFilterStartsWith(array $matrix)
    {
        $this->assertCount(3, $matrix);
        $filtered = ArrayFunctions::matrixFilterStartswith($matrix, 'foo', 'ba');
        $this->assertCount(2, $filtered);
    }
}
