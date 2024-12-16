<?php

declare(strict_types=1);

namespace N98\Util\Unicode;

use PHPUnit\Framework\TestCase;

final class CharsetTest extends TestCase
{
    public function testConvertInteger()
    {
        $this->assertSame('✖', Charset::convertInteger(Charset::UNICODE_CROSS_CHAR));
        $this->assertSame('✔', Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR));
    }
}
