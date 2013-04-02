<?php

namespace N98\Magento\Command\PHPUnit;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getApplication()
    {
        $root = getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');
        if (empty($root)) {
            throw new \RuntimeException(
                'Please specify environment variable N98_MAGERUN_TEST_MAGENTO_ROOT with path to your test
                magento installation!'
            );
        }
        $application = $this->getMock('N98\Magento\Application', array('getMagentoRootFolder', 'detectMagento'));
        $application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));

        return $application;
    }
}