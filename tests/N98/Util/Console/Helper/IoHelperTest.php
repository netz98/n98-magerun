<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util\Console\Helper;

use N98\Magento\Command\PHPUnit\TestCase;

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
        $this->assertInstanceOf('N98\Util\Console\Helper\IoHelper', $helper);
        $this->assertInstanceOf('Symfony\Component\Console\Helper\HelperInterface', $helper);
        $this->assertNull($helper->getOutput());

        $this->assertSame('io', $helper->getName());
    }
}
