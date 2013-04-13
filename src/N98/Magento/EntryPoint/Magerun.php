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
    public function _processRequest()
    {
        // NOP
    }
}