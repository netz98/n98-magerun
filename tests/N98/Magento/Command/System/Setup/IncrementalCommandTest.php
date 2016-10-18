<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\TestCase;

/**
 * Class IncrementalCommandTest
 *
 * @package N98\Magento\Command\System\Setup
 * @covers  N98\Magento\Command\System\Setup\IncrementalCommand
 */
class IncrementalCommandTest extends TestCase
{
    /**
     * @test
     * @link https://github.com/netz98/n98-magerun/pull/747
     */
    public function regression747()
    {
        $stub = new IncrementalCommandStub();

        $actual = $stub->callProtectedMethodFromObject('protectedMethod', $this, array("fooBar"));
        $this->assertSame('barBaz', $actual);
    }

    protected function protectedMethod($arg)
    {
        $this->assertSame('fooBar', $arg);
        $this->addToAssertionCount(1);

        return "barBaz";
    }
}
