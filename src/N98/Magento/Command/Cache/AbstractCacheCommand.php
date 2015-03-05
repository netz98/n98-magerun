<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Cache
     * @throws \Exception
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

    /**
     * Ban cache usage before cleanup to get the latest values.
     *
     * @see https://github.com/netz98/n98-magerun/issues/483
     */
    protected function banUseCache()
    {
        if (!$this->_canUseBanCacheFunction()) {
            return;
        }

        $config = $this->getApplication()->getConfig();
        if (empty($config['init']['options'])) {
            $config['init']['options'] = array('global_ban_use_cache' => true);
            $this->getApplication()->setConfig($config);
        }
    }

    protected function reinitCache()
    {
        if (!$this->_canUseBanCacheFunction()) {
            return;
        }

        \Mage::getConfig()->getOptions()->setData('global_ban_use_cache', false);
        \Mage::app()->baseInit(array()); // Re-init cache
        \Mage::getConfig()->loadModules()->loadDb()->saveCache();
    }

    /**
     * @return bool
     */
    protected function _canUseBanCacheFunction()
    {
        return method_exists('\Mage_Core_Model_App', 'baseInit');
    }
}
