<?php

declare(strict_types=1);

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
final class AutoloadRestorerTest extends TestCase
{
    public function testCreation()
    {
        $autoloadRestorer = new AutoloadRestorer();

        $this->assertInstanceOf(AutoloadRestorer::class, $autoloadRestorer);
    }

    public function testRestoration()
    {
        $callbackStub = function (): void {};

        $this->assertTrue(spl_autoload_register($callbackStub));

        $autoloadRestorer = new AutoloadRestorer();

        $this->assertContains($callbackStub, spl_autoload_functions());

        $this->assertTrue(spl_autoload_unregister($callbackStub));

        $this->assertNotContains($callbackStub, spl_autoload_functions());

        $autoloadRestorer->restore();

        $this->assertContains($callbackStub, spl_autoload_functions());
    }
}
