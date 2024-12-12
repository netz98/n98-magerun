<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Output\NullOutput;

final class ConfigurationLoaderTest extends TestCase
{
    public function testCreation()
    {
        $configurationLoader = new ConfigurationLoader([], false, new NullOutput());
        $this->assertInstanceOf(__NAMESPACE__ . '\\ConfigurationLoader', $configurationLoader);
    }
}
