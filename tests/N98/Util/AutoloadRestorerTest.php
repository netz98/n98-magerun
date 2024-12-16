<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;
/**
 * Class AutoloadRestorerTest
 *
 * @package N98\Util
 */
class AutoloadRestorerTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $autoloadRestorer = new AutoloadRestorer();

        self::assertInstanceOf(AutoloadRestorer::class, $autoloadRestorer);
    }

    /**
     * @test
     */
    public function restoration()
    {
        $callbackStub = function () {
        };

        self::assertTrue(spl_autoload_register($callbackStub));

        $autoloadRestorer = new AutoloadRestorer();

        self::assertContains($callbackStub, spl_autoload_functions());

        self::assertTrue(spl_autoload_unregister($callbackStub));

        self::assertNotContains($callbackStub, spl_autoload_functions());

        $autoloadRestorer->restore();

        self::assertContains($callbackStub, spl_autoload_functions());
    }
}
