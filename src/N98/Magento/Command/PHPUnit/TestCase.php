<?php

namespace N98\Magento\Command\PHPUnit;

use N98\Magento\Application;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class TestCase
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\PHPUnit
 */
class TestCase extends \PHPUnit_Framework_TestCase
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

        $root = getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');
        if (empty($root)) {
            $this->markTestSkipped(
                'Please specify environment variable N98_MAGERUN_TEST_MAGENTO_ROOT with path to your test ' .
                'magento installation!'
            );
        }

        $this->root = realpath($root);
        return $this->root;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Application
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            $this->application = $this->getMock(
                'N98\Magento\Application',
                array('getMagentoRootFolder')
            );
            $loader = require __DIR__ . '/../../../../../vendor/autoload.php';
            $this->application->setAutoloader($loader);
            $this->application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));

            spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));

            $this->application->init();
            $this->application->initMagento();
            if ($this->application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
                spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));
            }
        }

        return $this->application;
    }

    /**
     * @return \Varien_Db_Adapter_Pdo_Mysql
     */
    public function getDatabaseConnection()
    {
        return \Mage::getSingleton('core/resource')->getConnection('write');
    }
}
