<?php

declare(strict_types=1);

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class ExecTest
 *
 * @package N98\Util
 */
final class ExecTest extends TestCase
{
    public function testCommandOnly()
    {
        Exec::run('echo test', $output, $actual);

        $this->assertSame(0, $actual);
    }

    public function testFullParameters()
    {
        Exec::run('echo test', $commandOutput, $returnCode);

        $this->assertSame(Exec::CODE_CLEAN_EXIT, $returnCode);
        $this->assertStringStartsWith('test', $commandOutput);
    }

    public function testException()
    {
        $this->expectException(RuntimeException::class);
        Exec::run('foobar');
    }
}
