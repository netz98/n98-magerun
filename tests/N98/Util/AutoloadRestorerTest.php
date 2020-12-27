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

        self::assertInstanceOf('N98\Util\AutoloadRestorer', $restorer);
    }

    /**
     * @test
     */
    public function restoration()
    {
        $callbackStub = function () {
        };

        self::assertTrue(spl_autoload_register($callbackStub));

        $restorer = new AutoloadRestorer();

        self::assertTrue(in_array($callbackStub, spl_autoload_functions(), true));

        self::assertTrue(spl_autoload_unregister($callbackStub));

        self::assertFalse(in_array($callbackStub, spl_autoload_functions(), true));

        $restorer->restore();

        self::assertTrue(in_array($callbackStub, spl_autoload_functions(), true));
    }
}
