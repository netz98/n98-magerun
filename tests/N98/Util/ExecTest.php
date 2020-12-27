<?php

namespace N98\Util;

use Exception;
use RuntimeException;

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

        $this->assertSame(0, $actual);
    }

    /**
     * @test
     */
    public function fullParameters()
    {
        Exec::run('echo test', $commandOutput, $returnCode);

        $this->assertEquals(Exec::CODE_CLEAN_EXIT, $returnCode);
        $this->assertStringStartsWith('test', $commandOutput);
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
