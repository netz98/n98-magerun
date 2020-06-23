<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

/**
 * Class AutoloadRestorerTest
 *
 * @package N98\Util
 */
class AutoloadRestorerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $restorer = new AutoloadRestorer();

        $this->assertInstanceOf('N98\Util\AutoloadRestorer', $restorer);
    }

    /**
     * @test
     */
    public function restoration()
    {
        $callbackStub = function () {
        };

        $this->assertTrue(spl_autoload_register($callbackStub));

        $restorer = new AutoloadRestorer();

        $this->assertTrue(in_array($callbackStub, spl_autoload_functions(), true));

        $this->assertTrue(spl_autoload_unregister($callbackStub));

        $this->assertFalse(in_array($callbackStub, spl_autoload_functions(), true));

        $restorer->restore();

        $this->assertTrue(in_array($callbackStub, spl_autoload_functions(), true));
    }
}
