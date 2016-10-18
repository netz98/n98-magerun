<?php

namespace N98\Magento\Command;

use N98\Magento\Application;
use PHPUnit_Framework_MockObject_MockObject;
use RuntimeException;

/**
 * Class TestCase
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\PHPUnit
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $application = null;

    /**
     * @var string|null
     */
    private $root;

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

        $varname = 'N98_MAGERUN_TEST_MAGENTO_ROOT';
        $basename = '.n98-magerun';

        $root = self::getTestMagentoRootFromEnvironment($varname, $basename);

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

            /** @var Application|PHPUnit_Framework_MockObject_MockObject $application */
            $application = $this->getMock('N98\Magento\Application', array('getMagentoRootFolder'));

            // Get the composer bootstrap
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                $loader = require PHPUNIT_COMPOSER_INSTALL;
            } elseif (file_exists(__DIR__ . '/../../../../../../autoload.php')) {
                // Installed via composer, already in vendor
                $loader = require __DIR__ . '/../../../../../../autoload.php';
            } else {
                // Check if testing root package without PHPUnit
                $loader = require __DIR__ . '/../../../../vendor/autoload.php';
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

    /**
     * @return \Varien_Db_Adapter_Pdo_Mysql
     */
    public function getDatabaseConnection()
    {
        $resource = \Mage::getSingleton('core/resource');

        return $resource->getConnection('write');
    }
}
