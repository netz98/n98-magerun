<?php

declare(strict_types=1);

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
final class IoHelperTest extends TestCase
{
    public function testCreation()
    {
        $ioHelper = new IoHelper();
        $this->assertInstanceOf(IoHelper::class, $ioHelper);
        $this->assertInstanceOf(HelperInterface::class, $ioHelper);
        $this->assertNotInstanceOf(\Symfony\Component\Console\Output\OutputInterface::class, $ioHelper->getOutput());

        $this->assertSame('io', $ioHelper->getName());
    }
}
