<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class StringTypedTest
 *
 * @package N98\Util
 * @covers N98\Util\StringTyped
 */
final class StringTypedTest extends TestCase
{
    public function testScope()
    {
        $this->assertTrue(StringTyped::parseBoolOption('true'));

        $this->assertSame('inactive', StringTyped::formatActive(null));
        $this->assertSame('active', StringTyped::formatActive('1'));
    }
}
