<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util;

/**
 * Class WindowsSystemTest
 *
 * @package N98\Util
 */
class AutoloadRestorerTest extends \PHPUnit_Framework_TestCase
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
        $callbackStub = function() {
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
