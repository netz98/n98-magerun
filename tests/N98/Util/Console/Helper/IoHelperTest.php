<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper;

use N98\Magento\Command\TestCase;

/**
 * Class IoHelperTest
 *
 * @package N98\Util\Console\Helper
 */
class IoHelperTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $helper = new IoHelper();
        self::assertInstanceOf('N98\Util\Console\Helper\IoHelper', $helper);
        self::assertInstanceOf('Symfony\Component\Console\Helper\HelperInterface', $helper);
        self::assertNull($helper->getOutput());

        self::assertSame('io', $helper->getName());
    }
}
