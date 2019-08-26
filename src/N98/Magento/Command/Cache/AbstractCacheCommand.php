<?php

namespace N98\Magento\Command\Cache;

use InvalidArgumentException;
use Mage_Core_Model_Cache;
use N98\Magento\Application;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;

class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Cache
     *
     * @throws RuntimeException
     */
    protected function _getCacheModel()
    {
        if ($this->_magentoMajorVersion == AbstractMagentoCommand::MAGENTO_MAJOR_VERSION_2) {
            throw new RuntimeException('There global Mage class was removed from Magento 2. What should we do here?');
        } else {
            return \Mage::app()->getCacheInstance();
        }
    }

    /**
     * @param array $codeArgument
     * @param bool  $status
     * @return boolean|null
     */
    protected function saveCacheStatus($codeArgument, $status)
    {
        $this->validateCacheCodes($codeArgument);

        $cacheTypes = $this->_getCacheModel()->getTypes();
        $enable = \Mage::app()->useCache();
        foreach ($cacheTypes as $cacheCode => $cacheModel) {
            if (empty($codeArgument) || in_array($cacheCode, $codeArgument)) {
                $enable[$cacheCode] = $status ? 1 : 0;
            }
        }

        \Mage::app()->saveUseCache($enable);
    }

    /**
     * @param array $codes
     * @throws InvalidArgumentException
     */
    protected function validateCacheCodes(array $codes)
    {
        $cacheTypes = $this->_getCacheModel()->getTypes();
        foreach ($codes as $cacheCode) {
            if (!array_key_exists($cacheCode, $cacheTypes)) {
                throw new InvalidArgumentException('Invalid cache type: ' . $cacheCode);
            }
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
        \Mage::getConfig()->reinit();
    }

    /**
     * @return bool
     */
    protected function _canUseBanCacheFunction()
    {
        return method_exists('\Mage_Core_Model_App', 'baseInit');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->getMagentoMajorVersion() === Application::MAGENTO_MAJOR_VERSION_1;
    }
}
