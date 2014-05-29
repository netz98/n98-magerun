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
        return $this->_getModel('core/cache', 'Mage_Core_Model_Cache');
    }
}
