<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\TestCase;

/**
 * Class IncrementalCommandTest
 *
 * @package N98\Magento\Command\System\Setup
 * @covers  \N98\Magento\Command\System\Setup\IncrementalCommand
 */
final class IncrementalCommandTest extends TestCase
{
    /**
     * @link https://github.com/netz98/n98-magerun/pull/747
     */
    public function testRegression747()
    {
        $incrementalCommandStub = new IncrementalCommandStub();

        $actual = $incrementalCommandStub->callProtectedMethodFromObject('protectedMethod', $this, ['fooBar']);
        $this->assertSame('barBaz', $actual);
    }

    protected function protectedMethod($arg): string
    {
        $this->assertSame('fooBar', $arg);
        $this->addToAssertionCount(1);

        return 'barBaz';
    }
}
