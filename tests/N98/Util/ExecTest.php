<?php

namespace N98\Util;

use N98\Util\Exec;

class ExecTest extends \PHPUnit_Framework_TestCase{


    /**
     * @expectedException \RuntimeException
     */
    public function testRun() {

        $commandOutput = null;
        $returnCode = null;

        Exec::run('echo test', $commandOutput, $returnCode);

        $this->assertEquals(Exec::CODE_CLEAN_EXIT, $returnCode);
        $this->assertStringStartsWith('test', $commandOutput);


        Exec::run('foobar', $commandOutput, $returnCode);

        $this->fail('An expected Exception has not been thrown.');
    }
}