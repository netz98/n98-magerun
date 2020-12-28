<?php

namespace N98\Util;

/**
 * Class ExecTest
 *
 * @package N98\Util
 */
class ExecTest extends \PHPUnit\Framework\TestCase
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
        $this->expectException(\RuntimeException::class);
        Exec::run('foobar');
    }
}
