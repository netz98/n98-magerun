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
     * @var \N98\Magento\Application
     */
    private $application = null;

    /**
     * @throws \RuntimeException
     * @return PHPUnit_Framework_MockObject_MockObject|\N98\Magento\Application
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');
            if (empty($root)) {
                throw new \RuntimeException(
                    'Please specify environment variable N98_MAGERUN_TEST_MAGENTO_ROOT with path to your test
                    magento installation!'
                );
            }

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
