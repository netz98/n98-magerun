<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
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
        $ioHelper = new IoHelper();
        self::assertInstanceOf(IoHelper::class, $ioHelper);
        self::assertInstanceOf(HelperInterface::class, $ioHelper);
        self::assertNull($ioHelper->getOutput());

        self::assertSame('io', $ioHelper->getName());
    }
}
