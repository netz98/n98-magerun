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
}