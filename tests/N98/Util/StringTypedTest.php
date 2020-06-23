<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase as TestCase;

/**
 * Class StringTypedTest
 *
 * @package N98\Util
 * @covers N98\Util\StringTyped
 */
class StringTypedTest extends TestCase
{
    /**
     * @test
     */
    public function scope()
    {
        $this->assertSame(true, StringTyped::parseBoolOption("true"));

        $this->assertSame('inactive', StringTyped::formatActive(null));
        $this->assertSame('active', StringTyped::formatActive('1'));
    }
}
