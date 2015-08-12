<?php

namespace N98\Util;

class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @param string $string
     * @param string $delimiter
     * @param array  $expected
     * @dataProvider trimExplodeEmptyProvider
     */
    public function trimExplodeEmpty($delimiter, $string, $expected)
    {
        $this->assertEquals($expected, String::trimExplodeEmpty($delimiter, $string), '', 0.0, 10, true);
    }

    /**
     * @return array
     */
    public static function trimExplodeEmptyProvider()
    {
        return array(
            array(
                ',',
                'Foo,Bar',
                array('Foo', 'Bar')
            ),
            array(
                '#',
                ' Foo# Bar',
                array('Foo', 'Bar')
            ),
            array(
                ',',
                ',,Foo, Bar,,',
                array('Foo', 'Bar')
            ),
        );
    }

    /**
     * @test
     */
    public function startsWith()
    {
        $this->assertTrue(String::startsWith('Foo', 'Foo'));
        $this->assertTrue(String::startsWith('Foo123', 'Foo'));
        $this->assertFalse(String::startsWith(' Foo123', 'Foo'));
    }

    /**
     * @test
     */
    public function endsWith()
    {
        $this->assertTrue(String::endsWith('Foo', 'Foo'));
        $this->assertTrue(String::endsWith('Foo123', '123'));
        $this->assertFalse(String::endsWith(' Foo123 ', '123'));
    }
}