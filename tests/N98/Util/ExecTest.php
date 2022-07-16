<?php

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use RuntimeException;
/**
 * Class ExecTest
 *
 * @package N98\Util
 */
class ExecTest extends TestCase
{
    /**
     * @test
     */
    public function commandOnly()
    {
        Exec::run('echo test', $output, $actual);

        self::assertSame(0, $actual);
    }

    /**
     * @test
     */
    public function fullParameters()
    {
        Exec::run('echo test', $commandOutput, $returnCode);

        self::assertEquals(Exec::CODE_CLEAN_EXIT, $returnCode);
        self::assertStringStartsWith('test', $commandOutput);
    }

    /**
     * @test
     */
    public function exception()
    {
        $this->expectException(RuntimeException::class);
        Exec::run('foobar');
    }
}
