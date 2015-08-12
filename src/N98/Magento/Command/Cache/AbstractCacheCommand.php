<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Cache
     */
    protected function _getCacheModel()
    {
        if ($this->_magentoMajorVersion == AbstractMagentoCommand::MAGENTO_MAJOR_VERSION_2) {
            throw new \Exception('There global Mage class was removed from Magento 2. What should we do here?');
            return \Mage::getModel('Mage_Core_Model_Cache');
        } else {
            return \Mage::app()->getCacheInstance();
        }
    }
}
