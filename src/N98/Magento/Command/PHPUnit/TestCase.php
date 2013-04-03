<?php

namespace N98\Magento\Command\PHPUnit;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \N98\Magento\Application
     */
    private $application = null;

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\N98\Magento\Application
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

            $this->application = $this->getMock('N98\Magento\Application', array('getMagentoRootFolder', 'detectMagento'));
            $this->application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));
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