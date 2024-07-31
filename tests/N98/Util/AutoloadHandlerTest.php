<?php
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
class AutoloadHandlerTest extends TestCase
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

    /**
     * @test
     */
    public function creation()
    {
        $handler = $this->create(null);
        self::assertInstanceOf(__NAMESPACE__ . '\AutoloadHandler', $handler);
        self::assertIsCallable($handler);
    }

    /**
     * @test
     */
    public function noRegistrationOnCreation(): never
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Autoload callback is not callable');

        $handler = $this->create(null, AutoloadHandler::NO_AUTO_REGISTER);
        $handler->disable(); // assertions require a disabled handler b/c of exceptions

        self::assertNotContains($handler, spl_autoload_functions());
        self::assertFalse($handler->__invoke('test'));
        $handler->register();
        $actual = in_array($handler, spl_autoload_functions());
        self::assertTrue($actual);

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

    /**
     * @test
     */
    public function registrationAndDeregistration()
    {
        $calls = (object) ['retval' => true];
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = [$className];
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create($assertAble);
        self::assertTrue($handler->isEnabled());
        self::assertTrue($handler->__invoke('Fake'));

        $handler->unregister();
        self::assertFalse($handler->__invoke('Fake'));
        self::assertEquals(1, $calls->count['Fake']);
    }

    /**
     * @test
     */
    public function changingCallback()
    {
        $calls = (object) ['retval' => true];
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = [$className];
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create(null, AutoloadHandler::NO_EXCEPTION);
        self::assertFalse($handler->__invoke('Test'));
        self::assertObjectNotHasAttribute('count', $calls);

        $handler->setCallback($assertAble);
        self::assertTrue($handler->__invoke('Test'));
        self::assertEquals(1, $calls->count['Test']);

        $handler->setCallback(null);
        self::assertFalse($handler->__invoke('Test'));
        self::assertEquals(1, $calls->count['Test']);
    }

    /**
     * @test
     */
    public function disablingAndEnabling(): never
    {
        $handler = $this->create(null);
        $handler->setEnabled(false);
        self::assertFalse($handler->__invoke('Test'));
        $handler->setEnabled(true);
        $this->expectException(BadMethodCallException::class);
        self::assertFalse($handler->__invoke('Test'));
        self::fail('An expected exception has not been thrown');
    }

    /**
     * @test
     */
    public function callbackSelfReference()
    {
        $testClass = 'MyOf' . random_int(1000, 9999) . 'Fake' . random_int(1000, 9999) . 'Class';
        $test = $this;
        $handler = $this->create(function ($className) use (&$handler, $test, $testClass) {
            /** @var $handler AutoloadHandler */
            $test->assertEquals($testClass, $className);
            $handler->disable();
        });
        $actual = class_exists($testClass);
        $isEnabled = $handler->isEnabled();
        self::assertEquals(1, self::getCount());
        self::assertFalse($isEnabled);
        self::assertFalse($actual);
    }

    /**
     * @test
     */
    public function cleanupCallback()
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
        self::assertFalse($actual);
        self::assertContains($handler, spl_autoload_functions(), 'before cleanup');
        $cleanup();
        self::assertNotContains($handler, spl_autoload_functions(), 'after cleanup');
        // calling cleanup again must not do any warnings etc.
        $cleanup();
    }
}
