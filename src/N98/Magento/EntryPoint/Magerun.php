<?php

namespace N98\Magento\EntryPoint;

/**
 * Class Magerun
 * This is required for Magento 2
 *
 * @codeCoverageIgnore
 * @package N98\Magento\EntryPoint
 */
class Magerun extends \Mage_Core_Model_EntryPointAbstract
{
    /**
     * @param string $baseDir
     * @param array $params
     */
    public function __construct($baseDir, array $params = array())
    {
        $this->_params = $params;
        $config = new \Mage_Core_Model_Config_Primary($baseDir, $this->_params);
        parent::__construct($config);
    }

    public function processRequest()
    {
        // NOP
    }
}
