<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class ConfigurationLoaderTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $loader = new ConfigurationLoader([], false, new NullOutput());
        self::assertInstanceOf(__NAMESPACE__ . '\\ConfigurationLoader', $loader);
    }
}
