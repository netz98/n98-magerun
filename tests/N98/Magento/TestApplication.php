<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\MockObject\MockObject;
use Varien_Autoload;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Magento test-application, the one used in unit and integration testing.
 *
 * @package N98\Magento
 */
class TestApplication
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var string|null
     */
    private $root;

    /**
     * @var string
     */
    private $varname;

    /**
     * @var string
     */
    private $basename;

    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @param string $varname name of the environment variable containing the test-root
     * @param string $basename name of the stopfile containing the test-root
     *
     * @return string|null
     */
    public static function getTestMagentoRootFromEnvironment($varname, $basename)
    {
        $root = getenv($varname);
        if (empty($root) && strlen($basename)) {
            $stopfile = getcwd() . '/' . $basename;
            if (is_readable($stopfile) && $buffer = rtrim(file_get_contents($stopfile))) {
                $root = $buffer;
            }
        }
        if (empty($root)) {
            return;
        }

        # directory test
        if (!is_dir($root)) {
            throw new RuntimeException(
                sprintf("%s path '%s' is not a directory (cwd: '%s', stopfile: '%s')", $varname, $root, getcwd(), $stopfile ?? '')
            );
        }

        # resolve root to realpath to be independent to current working directory
        $rootRealpath = realpath($root);
        if (false === $rootRealpath) {
            throw new RuntimeException(
                sprintf("Failed to resolve %s path '%s' with realpath()", $varname, $root)
            );
        }

        return $rootRealpath;
    }

    /**
     * @param TestCase $testCase
     * @return array
     */
    public static function getConfig(TestCase $testCase)
    {
        $testApplication = new TestApplication($testCase);

        return $testApplication->getApplication()->getConfig();
    }

    /**
     * TestApplication constructor.
     *
     * @param TestCase $testCase
     * @param null $varname [optional] name of the environment variable containing the path to magento-root, "N98_MAGERUN_TEST_MAGENTO_ROOT" by default
     * @param null $basename [optional] of the stop-file, ".n98-magerun" by default
     */
    public function __construct(TestCase $testCase, $varname = null, $basename = null)
    {
        if (null === $varname) {
            $varname = 'N98_MAGERUN_TEST_MAGENTO_ROOT';
        }
        if (null === $basename) {
            $basename = '.n98-magerun';
        }
        $this->testCase = $testCase;
        $this->varname = $varname;
        $this->basename = $basename;
    }

    /**
     * getter for the magento root directory of the test-suite
     *
     * @see ApplicationTest::testExecute
     *
     * @return string
     */
    public function getTestMagentoRoot()
    {
        if ($this->root) {
            return $this->root;
        }

        $varname = $this->varname;

        $root = self::getTestMagentoRootFromEnvironment($varname, $this->basename);

        if (null === $root) {
            throw new SkippedTestError(
                "Please specify environment variable $varname with path to your test magento installation!"
            );
        }

        return $this->root = $root;
    }

    /**
     * @return Application|MockObject
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            /** @var Application|MockObject $application */
            $application = $this->testCase->getMockBuilder(Application::class)
                ->setMethods(['getMagentoRootFolder'])
                ->getMock();

            // Get the composer bootstrap
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                $loader = require PHPUNIT_COMPOSER_INSTALL;
            } elseif (is_file(__DIR__ . '/../../../../../autoload.php')) {
                // Installed via composer, already in vendor
                $loader = require __DIR__ . '/../../../../../autoload.php';
            } else {
                // Check if testing root package without PHPUnit
                $loader = require __DIR__ . '/../../../vendor/autoload.php';
            }

            $application->setAutoloader($loader);
            $application->method('getMagentoRootFolder')->willReturn($root);

            spl_autoload_unregister([Varien_Autoload::instance(), 'autoload']);

            $application->init();
            $application->initMagento();

            spl_autoload_unregister([Varien_Autoload::instance(), 'autoload']);

            $this->application = $application;
        }

        return $this->application;
    }
}
