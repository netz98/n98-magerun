<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _getCacheModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Core_Model_Cache');
        } else {
            return \Mage::getModel('core/cache');
        }
    }
}
