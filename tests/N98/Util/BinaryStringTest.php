<?php

namespace N98\Util;

use PHPUnit\Framework\TestCase;
/**
 * Class BinaryStringTest
 *
 * @covers \N98\Util\BinaryString
 */
class BinaryStringTest extends TestCase
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
        self::assertEqualsCanonicalizing($expected, BinaryString::trimExplodeEmpty($delimiter, $string));
    }

    /**
     * @return array
     */
    public function trimExplodeEmptyProvider()
    {
        return [[',', 'Foo,Bar', ['Foo', 'Bar']], ['#', ' Foo# Bar', ['Foo', 'Bar']], [',', ',,Foo, Bar,,', ['Foo', 'Bar']]];
    }
}
