<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Util\AutoloadHandler;
use \PHPUnit\Framework\Error\Warning;

/**
 * Class ClassExistsCheckerTest
 *
 * @covers \N98\Magento\Command\Developer\Module\Rewrite\ClassExistsChecker
 */
class ClassExistsCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $cleanup = array();

    protected function tearDown()
    {
        $this->cleanup();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function creation()
    {
        $checker = new ClassExistsChecker('Le_Foo_Le_Bar_Nexiste_Pas');
        $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsChecker', $checker);

        $checker = ClassExistsChecker::create('Le_Foo_Le_Bar_Nexiste_Pas');
        $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsChecker', $checker);
    }

    /**
     * @test
     */
    public function existingClass()
    {
        $this->assertTrue(ClassExistsChecker::create('IteratorIterator')->existsExtendsSafe());
    }

    /**
     * @test
     */
    public function nonExistingClass()
    {
        $this->assertFalse(ClassExistsChecker::create('asdfu8jq23nklr08asASDF0oaosdufhoanl')->existsExtendsSafe());
    }

    /**
     * @test
     */
    public function throwingAnExceptionWhileIncluding()
    {
        // similar to Varien_Autoload
        $innerException = null;
        $autoload = $this->create(function ($className) use (&$innerException) {
            $innerException = new \BadMethodCallException('exception in include simulation for ' . $className);
            throw $innerException;
        });

        try {
            $className = 'Le_Foo_Le_Bar_Nexiste_Pas';
            ClassExistsChecker::create($className)->existsExtendsSafe();
            $autoload->reset();
            $this->fail('An expected Exception has not been thrown');
        } catch (\Exception $ex) {
            $autoload->reset();
            $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsThrownException', $ex);
            $this->assertTrue($ex->getPrevious() instanceof $innerException);
            $this->assertSame($innerException, $ex->getPrevious());
        }
    }

    /**
     * @return array
     * @see preventingFatalOnNonExistingBaseClass
     */
    public function provideClassNames()
    {
        return array(
            array('Le_Foo_Le_Bar'), # extends from a non-existing file of that base-class
            array('Le_Foo_Le_Bar_R1'), # extends from a dynamic include of non-existence
        );
    }

    /**
     * @test
     * @dataProvider provideClassNames
     * @param string $className
     */
    public function preventingFatalOnNonExistingBaseClass($className)
    {
        $autoload = $this->create($this->getAutoloader());
        $restore = $this->noErrorExceptions();
        try {
            $actual = ClassExistsChecker::create($className)->existsExtendsSafe();
            $restore();
            $autoload->reset();
            $this->assertFalse($actual);
        } catch (\Exception $ex) {
            $restore();
            $autoload->reset();
            $this->fail('An exception has been thrown');
        }
    }

    /**
     * @test
     */
    public function warningTriggeringExpectedBehaviour()
    {
        // reset last error
        set_error_handler('var_dump', 0);
        @$undef_var;
        restore_error_handler();
        $canary = error_get_last();

        // precondition is that there was no error yet
        $this->assertNotNull($canary, 'precondition not met');

        // precondition of the error reporting level
        $reporting = error_reporting();
        // 22527 - E_ALL & ~E_DEPRECATED & ~E_STRICT (PHP 5.6)
        // 32767 - E_ALL (Travis PHP 5.3, PHP 5.4)
        $knownErrorLevels = array(
            'E_ALL & ~E_DEPRECATED & ~E_STRICT (Deb Sury 5.6)' => 22527,
            'E_ALL (Travis PHP 5.3, 5.4, 5.5)'                 => 32767,
        );
        $this->assertTrue(in_array($reporting, $knownErrorLevels), "error reporting as of $reporting");

        // by default the class must be loaded with a different autoloader
        $this->assertFalse(class_exists('Le_Foo_Le_Bar_Fine'));

        // post-condition is that there was no error yet
        $this->assertSame($canary, error_get_last());

        // should not trigger an error if the class exists
        $autoload = $this->create($this->getAutoloader());
        $this->assertTrue(class_exists('Le_Foo_Le_Bar_Fine'));
        $this->assertSame($canary, error_get_last());

        // should trigger a warning if the class does not exists as file on disk per auto-loading
        $restore = $this->noErrorExceptions();
        $actual = class_exists('Le_Foo_Le_Bar_Nexiste_Pas');
        $restore();

        $this->assertFalse($actual);
        $lastError = error_get_last();
        if ($canary === $lastError) {
            $this->markTestIncomplete('System does not triggers the expected warning on include');
        }

        $this->assertNotSame($canary, $lastError);
        $this->assertArrayHasKey('type', $lastError);
        $this->assertSame(2, $lastError['type']);
        $this->assertArrayHasKey('message', $lastError);
        $pattern = '~include\(\): Failed opening \'.*Rewrite/fixture/Le_Foo_Le_Bar_Nexiste_Pas\.php\' for inclusion ~';
        $this->assertRegExp($pattern, $lastError['message']);
    }

    /**
     * Document the condition in which the Varien_Autoload auto-loader causes a fatal error
     *
     * @test
     */
    public function triggersFatalError()
    {
        $this->markTestSkipped('This test can not be run in group as it causes a fatal error');

        // fatal error is caused with plain class_exists on non-dynamic definition with inexistent parent via autoloader
        $unload = $this->create($this->getAutoloader());
        $reset = $this->noErrorExceptions(false);
        $result = class_exists('Le_Foo_Le_Bar');
        $this->fail('Fatal error must have been triggered in the line above.');
    }

    /**
     * Returns an auto-loader callback that is similar to Varien_Autoload
     *
     * @return \Closure
     */
    private function getAutoloader()
    {
        return function ($className) {
            if (!preg_match('~^(Le_Foo_Le_Bar)~', $className)) {
                return false;
            }
            $file = __DIR__ . '/fixture/' . $className . '.php';

            return include $file;
        };
    }

    /**
     * Disable PHPUnit error exceptions, returns a reset function to restore the original setting
     *
     * Private helper function for this test-case.
     *
     * @return \Closure
     */
    private function noErrorExceptions($includeIni = true)
    {
        $displayErrorsOrig = ini_get('display_errors');
        $includeIni && ini_set('display_errors', false);

        $logErrorsOrig = ini_get('log_errors');
        $includeIni && ini_set('log_errors', false);

        $warningEnabledOrig = \PHPUnit\Framework\Error\Warning::$enabled;
        \PHPUnit\Framework\Error\Warning::$enabled = false;

        $restore = function () use ($displayErrorsOrig, $logErrorsOrig, $warningEnabledOrig) {
            ini_set('display_errors', $displayErrorsOrig);
            ini_set('log_errors', $logErrorsOrig);
            \PHPUnit\Framework\Error\Warning::$enabled = $warningEnabledOrig;
        };

        $this->cleanup[] = $restore;

        return $restore;
    }

    /**
     * Private helper function to create an autoloader that get's automatically cleaned up
     * after test is over
     *
     * @param $callback
     * @param null $flags
     * @return AutoloadHandler
     */
    private function create($callback, $flags = null)
    {
        $handler = AutoloadHandler::create($callback, $flags);
        $this->cleanup[] = $handler->getCleanupCallback();
        return $handler;
    }

    private function cleanup()
    {
        foreach ($this->cleanup as $key => $cleanupTask) {
            $cleanupTask();
            unset($this->cleanup[$key]);
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
