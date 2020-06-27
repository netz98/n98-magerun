<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

/**
 * Class AutoloadHandlerTest
 *
 * @covers \N98\Util\AutoloadHandler
 * @package N98\Util
 */
class AutoloadHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $cleanup = array();

    public function tearDown()
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
        $this->assertInstanceOf(__NAMESPACE__ . '\AutoloadHandler', $handler);
        $this->assertInternalType('callable', $handler);
    }

    /**
     * @test
     */
    public function noRegistrationOnCreation()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Autoload callback is not callable');

        $handler = $this->create(null, AutoloadHandler::NO_AUTO_REGISTER);
        $handler->disable(); // assertions require a disabled handler b/c of exceptions

        $this->assertFalse(in_array($handler, spl_autoload_functions()));
        $this->assertFalse($handler->__invoke('test'));
        $handler->register();
        $actual = in_array($handler, spl_autoload_functions());
        $this->assertTrue($actual);

        $handler->enable();
        $handler->__invoke('test');
        $this->fail('An expected exception was not thrown');
    }

    private function create($implementation, $flags = null)
    {
        $handler = AutoloadHandler::create($implementation, $flags);
        $this->cleanup[] = $handler->getCleanupCallback();

        return $handler;
    }

    /**
     * @test
     */
    public function registrationAndDeregistration()
    {
        $calls = (object) array('retval' => true);
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = array($className);
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create($assertAble);
        $this->assertTrue($handler->isEnabled());
        $this->assertTrue($handler->__invoke("Fake"));

        $handler->unregister();
        $this->assertFalse($handler->__invoke("Fake"));
        $this->assertEquals(1, $calls->count['Fake']);
    }

    /**
     * @test
     */
    public function changingCallback()
    {
        $calls = (object) array('retval' => true);
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = array($className);
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create(null, AutoloadHandler::NO_EXCEPTION);
        $this->assertFalse($handler->__invoke("Test"));
        $this->assertObjectNotHasAttribute('count', $calls);

        $handler->setCallback($assertAble);
        $this->assertTrue($handler->__invoke("Test"));
        $this->assertEquals(1, $calls->count["Test"]);

        $handler->setCallback(null);
        $this->assertFalse($handler->__invoke("Test"));
        $this->assertEquals(1, $calls->count["Test"]);
    }

    /**
     * @test
     */
    public function disablingAndEnabling()
    {
        $handler = $this->create(null);
        $handler->setEnabled(false);
        $this->assertFalse($handler->__invoke("Test"));
        $handler->setEnabled(true);
        $this->expectException(\BadMethodCallException::class);
        $this->assertFalse($handler->__invoke("Test"));
        $this->fail('An expected exception has not been thrown');
    }

    /**
     * @test
     */
    public function callbackSelfReference()
    {
        $testClass = 'MyOf' . mt_rand(1000, 9999) . 'Fake' . mt_rand(1000, 9999) . 'Class';
        $test = $this;
        $handler = $this->create(function ($className) use (&$handler, $test, $testClass) {
            /** @var $handler AutoloadHandler */
            $test->assertEquals($testClass, $className);
            $handler->disable();
        });
        $actual = class_exists($testClass);
        $isEnabled = $handler->isEnabled();
        $this->assertEquals(1, $this->getCount());
        $this->assertFalse($isEnabled);
        $this->assertFalse($actual);
    }

    /**
     * @test
     */
    public function cleanupCallback()
    {
        $calls = (object) array('retval' => true);
        $assertAble = function ($className) use (&$calls) {
            $calls->log[] = array($className);
            $calls->count[$className] = 1 + @$calls->count[$className];

            return $calls->retval;
        };

        $handler = $this->create($assertAble, AutoloadHandler::NO_EXCEPTION);
        $cleanup = $handler->getCleanupCallback();
        $actual = class_exists('Test');
        $this->assertFalse($actual);
        $this->assertTrue(in_array($handler, spl_autoload_functions()), 'before cleanup');
        $cleanup();
        $this->assertFalse(in_array($handler, spl_autoload_functions()), 'after cleanup');
        // calling cleanup again must not do any warnings etc.
        $cleanup();
    }
}
