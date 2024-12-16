<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Developer\Module\Rewrite;

use PHPUnit\Framework\TestCase;
use IteratorIterator;
use BadMethodCallException;
use Exception;
use Closure;
use PHPUnit\Framework\Error\Warning;
use N98\Util\AutoloadHandler;

/**
 * Class ClassExistsCheckerTest
 *
 * @covers \N98\Magento\Command\Developer\Module\Rewrite\ClassExistsChecker
 */
final class ClassExistsCheckerTest extends TestCase
{
    private array $cleanup = [];

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testCreation()
    {
        $checker = new ClassExistsChecker('Le_Foo_Le_Bar_Nexiste_Pas');
        $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsChecker', $checker);

        $checker = ClassExistsChecker::create('Le_Foo_Le_Bar_Nexiste_Pas');
        $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsChecker', $checker);
    }

    public function testExistingClass()
    {
        $this->assertTrue(ClassExistsChecker::create(IteratorIterator::class)->existsExtendsSafe());
    }

    public function testNonExistingClass()
    {
        $this->assertFalse(ClassExistsChecker::create('asdfu8jq23nklr08asASDF0oaosdufhoanl')->existsExtendsSafe());
    }

    public function testThrowingAnExceptionWhileIncluding()
    {
        // similar to Varien_Autoload
        $innerException = null;
        $autoloadHandler = $this->create(function ($className) use (&$innerException): void {
            $innerException = new BadMethodCallException('exception in include simulation for ' . $className);
            throw $innerException;
        });

        try {
            $className = 'Le_Foo_Le_Bar_Nexiste_Pas';
            ClassExistsChecker::create($className)->existsExtendsSafe();
            $autoloadHandler->reset();
            self::fail('An expected Exception has not been thrown');
        } catch (Exception $exception) {
            $autoloadHandler->reset();
            $this->assertInstanceOf(__NAMESPACE__ . '\ClassExistsThrownException', $exception);
            if (isset($innerException)) {
                $this->assertInstanceOf(get_class($innerException), $exception->getPrevious());
            }

            $this->assertSame($innerException, $exception->getPrevious());
        }
    }

    /**
     * @return \Iterator<(int | string), mixed>
     * @see preventingFatalOnNonExistingBaseClass
     */
    public function provideClassNames(): \Iterator
    {
        yield ['Le_Foo_Le_Bar'];
        # extends from a non-existing file of that base-class
        yield ['Le_Foo_Le_Bar_R1'];
    }

    /**
     * @dataProvider provideClassNames
     * @param string $className
     */
    public function testPreventingFatalOnNonExistingBaseClass($className)
    {
        $autoloadHandler = $this->create($this->getAutoloader());
        $restore = $this->noErrorExceptions();
        try {
            $actual = ClassExistsChecker::create($className)->existsExtendsSafe();
            $restore();
            $autoloadHandler->reset();
            $this->assertFalse($actual);
        } catch (Exception $exception) {
            $restore();
            $autoloadHandler->reset();
            self::fail('An exception has been thrown');
        }
    }

    public function testWarningTriggeringExpectedBehaviour()
    {
        $this->markTestSkipped('Maybe not compatible with PHP 8.1 anymore. Has to be checked again.');
        $undef_var = null;
        // reset last error
        set_error_handler('var_dump', 0);
        /** @noinspection PhpExpressionResultUnusedInspection */
        @$undef_var;
        restore_error_handler();
        $canary = error_get_last();

        // precondition is that there was no error yet
        $this->assertNotNull($canary, 'precondition not met');

        // precondition of the error reporting level
        $reporting = error_reporting();
        // 22527 - E_ALL & ~E_DEPRECATED & ~E_STRICT (PHP 5.6)
        // 32767 - E_ALL (Travis PHP 5.3, PHP 5.4)
        $knownErrorLevels = ['E_ALL & ~E_DEPRECATED & ~E_STRICT (Deb Sury 5.6)' => 22527, 'E_ALL (Travis PHP 5.3, 5.4, 5.5)'                 => 32767];
        $this->assertContains($reporting, $knownErrorLevels, 'error reporting as of ' . $reporting);

        // by default the class must be loaded with a different autoloader
        $this->assertFalse(class_exists('Le_Foo_Le_Bar_Fine'));

        // post-condition is that there was no error yet
        $this->assertSame($canary, error_get_last());

        // should not trigger an error if the class exists
        $this->create($this->getAutoloader());
        $this->assertTrue(class_exists('Le_Foo_Le_Bar_Fine'));
        $this->assertSame($canary, error_get_last());

        // should trigger a warning if the class does not exists as file on disk per auto-loading
        $restore = $this->noErrorExceptions();
        $actual = class_exists('Le_Foo_Le_Bar_Nexiste_Pas');
        $restore();

        $this->assertFalse($actual);
        $lastError = error_get_last();
        if ($canary === $lastError) {
            self::markTestIncomplete('System does not triggers the expected warning on include');
        }

        $this->assertNotSame($canary, $lastError);
        $this->assertArrayHasKey('type', $lastError);
        $this->assertSame(2, $lastError['type']);
        $this->assertArrayHasKey('message', $lastError);
        $pattern = '~include\(\): Failed opening \'.*Rewrite/fixture/Le_Foo_Le_Bar_Nexiste_Pas\.php\' for inclusion ~';
        $this->assertMatchesRegularExpression($pattern, $lastError['message']);
    }

    /**
     * Returns an auto-loader callback that is similar to Varien_Autoload
     *
     * @return Closure
     */
    private function getAutoloader()
    {
        return function ($className) {
            if (in_array(preg_match('~^(Le_Foo_Le_Bar)~', $className), [0, false], true)) {
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
     * @return Closure
     */
    private function noErrorExceptions($includeIni = true)
    {
        $displayErrorsOrig = ini_get('display_errors');
        $includeIni && ini_set('display_errors', '0');

        $logErrorsOrig = ini_get('log_errors');
        $includeIni && ini_set('log_errors', '0');

        $restore = function () use ($displayErrorsOrig, $logErrorsOrig): void {
            ini_set('display_errors', $displayErrorsOrig);
            ini_set('log_errors', $logErrorsOrig);
        };

        $this->cleanup[] = $restore;

        return $restore;
    }

    /**
     * Private helper function to create an autoloader that get's automatically cleaned up
     * after test is over
     *
     * @param $callback
     * @return AutoloadHandler
     */
    private function create($callback, $flags = null)
    {
        $autoloadHandler = AutoloadHandler::create($callback, $flags);
        $this->cleanup[] = $autoloadHandler->getCleanupCallback();
        return $autoloadHandler;
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
