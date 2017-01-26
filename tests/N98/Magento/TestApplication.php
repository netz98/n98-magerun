<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use PHPUnit_Framework_MockObject_Generator;
use PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_MockObject_Stub_Return;
use PHPUnit_Framework_SkippedTestError;
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
                sprintf("%s path '%s' is not a directory", $varname, $root)
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
     * @return array
     */
    public static function getConfig()
    {
        $testApplication = new TestApplication();
        $config = $testApplication->getApplication()->getConfig();

        return $config;
    }

    /**
     * TestApplication constructor.
     *
     * @param string $varname [optional] name of the environment variable containing the path to magento-root
     */
    public function __construct($varname = null, $basename = null)
    {
        if (null === $varname) {
            $varname = 'N98_MAGERUN_TEST_MAGENTO_ROOT';
        }
        if (null === $basename) {
            $basename = '.n98-magerun';
        }
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
            $this->markTestSkipped(
                "Please specify environment variable $varname with path to your test magento installation!"
            );
        }

        return $this->root = $root;
    }

    /**
     * @return Application|PHPUnit_Framework_MockObject_MockObject
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            $mockObjectGenerator = new PHPUnit_Framework_MockObject_Generator;

            /** @var Application|PHPUnit_Framework_MockObject_MockObject $application */
            $application = $mockObjectGenerator->getMock('N98\Magento\Application', array('getMagentoRootFolder'));

            // Get the composer bootstraph
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                $loader = require PHPUNIT_COMPOSER_INSTALL;
            } elseif (file_exists(__DIR__ . '/../../../../../autoload.php')) {
                // Installed via composer, already in vendor
                $loader = require __DIR__ . '/../../../../../autoload.php';
            } else {
                // Check if testing root package without PHPUnit
                $loader = require __DIR__ . '/../../../vendor/autoload.php';
            }

            $application->setAutoloader($loader);
            $application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));

            spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));

            $application->init();
            $application->initMagento();

            if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
                spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));
            }

            $this->application = $application;
        }

        return $this->application;
    }

    /*
     * PHPUnit TestCase methods
     */

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is executed zero or more times.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount
     * @since  Method available since Release 3.0.0
     */
    public static function any()
    {
        return new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount;
    }

    /**
     *
     *
     * @param  mixed $value
     * @return PHPUnit_Framework_MockObject_Stub_Return
     * @since  Method available since Release 3.0.0
     */
    public static function returnValue($value)
    {
        return new PHPUnit_Framework_MockObject_Stub_Return($value);
    }

    /**
     * Mark the test as skipped.
     *
     * @param  string $message
     * @throws PHPUnit_Framework_SkippedTestError
     * @since  Method available since Release 3.0.0
     */
    public static function markTestSkipped($message = '')
    {
        throw new PHPUnit_Framework_SkippedTestError($message);
    }
}
