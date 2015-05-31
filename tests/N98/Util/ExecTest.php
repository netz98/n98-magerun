<?php

namespace N98\Util;

use Exception;
use RuntimeException;

/**
 * Class ExecTest
 *
 * @package N98\Util
 */
class ExecTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function commandOnly()
    {
        Exec::run('echo test');

        $this->addToAssertionCount(1);
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
     *
     * @expectedException RuntimeException
     */
    public function exception()
    {
        Exec::run('foobar');
    }
}
