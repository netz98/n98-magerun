<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use BadMethodCallException;

/**
 * Class AutoloadHandlerTest
 *
 * @covers \N98\Util\AutoloadHandler
 * @package N98\Util
 */
final class AutoloadHandlerTest extends TestCase
{
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $key => $task) {
            $task();
            unset($this->cleanup[$key]);
        }

        parent::tearDown();
    }

    public function testCreation()
    {
        $handler = $this->create(null);
        $this->assertInstanceOf(__NAMESPACE__ . '\AutoloadHandler', $handler);
        $this->assertIsCallable($handler);
    }

    public function testNoRegistrationOnCreation(): never
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Autoload callback is not callable');

        $handler = $this->create(null, AutoloadHandler::NO_AUTO_REGISTER);
        $handler->disable(); // assertions require a disabled handler b/c of exceptions

        $this->assertNotContains($handler, spl_autoload_functions());
        $this->assertFalse($handler->__invoke('test'));
        $handler->register();
        $actual = in_array($handler, spl_autoload_functions());
        $this->assertTrue($actual);

        $handler->enable();
        $handler->__invoke('test');
        self::fail('An expected exception was not thrown');
    }

    private function create($implementation, $flags = null)
    {
        $autoloadHandler = AutoloadHandler::create($implementation, $flags);
        $this->cleanup[] = $autoloadHandler->getCleanupCallback();

        return $autoloadHandler;
    }

    public function testRegistrationAndDeregistration()
    {
        $calls = (object) ['retval' => true];
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = [$className];
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create($assertAble);
        $this->assertTrue($handler->isEnabled());
        $this->assertTrue($handler->__invoke('Fake'));

        $handler->unregister();
        $this->assertFalse($handler->__invoke('Fake'));
        $this->assertSame(1, $calls->count['Fake']);
    }

    public function testChangingCallback()
    {
        $calls = (object) ['retval' => true];
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = [$className];
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create(null, AutoloadHandler::NO_EXCEPTION);
        $this->assertFalse($handler->__invoke('Test'));
        $this->assertObjectNotHasProperty('count', $calls);

        $handler->setCallback($assertAble);
        $this->assertTrue($handler->__invoke('Test'));
        $this->assertSame(1, $calls->count['Test']);

        $handler->setCallback(null);
        $this->assertFalse($handler->__invoke('Test'));
        $this->assertSame(1, $calls->count['Test']);
    }

    public function testDisablingAndEnabling(): never
    {
        $handler = $this->create(null);
        $handler->setEnabled(false);
        $this->assertFalse($handler->__invoke('Test'));
        $handler->setEnabled(true);
        $this->expectException(BadMethodCallException::class);
        $this->assertFalse($handler->__invoke('Test'));
        self::fail('An expected exception has not been thrown');
    }

    public function testCallbackSelfReference()
    {
        $testClass = 'MyOf' . random_int(1000, 9999) . 'Fake' . random_int(1000, 9999) . 'Class';
        $test = $this;
        $handler = $this->create(function ($className) use (&$handler, $test, $testClass): void {
            /** @var $handler AutoloadHandler */
            $test->assertSame($testClass, $className);
            $handler->disable();
        });
        $actual = class_exists($testClass);
        $isEnabled = $handler->isEnabled();
        $this->assertSame(1, self::getCount());
        $this->assertFalse($isEnabled);
        $this->assertFalse($actual);
    }

    public function testCleanupCallback()
    {
        $calls = (object) ['retval' => true];
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = [$className];
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create($assertAble, AutoloadHandler::NO_EXCEPTION);
        $cleanup = $handler->getCleanupCallback();
        $actual = class_exists('Test');
        $this->assertFalse($actual);
        $this->assertContains($handler, spl_autoload_functions(), 'before cleanup');
        $cleanup();
        $this->assertNotContains($handler, spl_autoload_functions(), 'after cleanup');
        // calling cleanup again must not do any warnings etc.
        $cleanup();
    }
}
