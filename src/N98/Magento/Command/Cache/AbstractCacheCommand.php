<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Cache;
use N98\Magento\Application;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;

/**
 * Class AbstractCacheCommand
 *
 * @package N98\Magento\Command\Cache
 */
class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @throws RuntimeException
     */
    protected function _getCacheModel(): Mage_Core_Model_Cache
    {
        return Mage::app()->getCacheInstance();
    }

    protected function saveCacheStatus(array $codeArgument, bool $status): void
    {
        $this->validateCacheCodes($codeArgument);

        $cacheTypes = $this->_getCacheModel()->getTypes();
        $enable     = Mage::app()->useCache();
        if ($enable) {
            foreach ($cacheTypes as $cacheCode => $cacheModel) {
                if ($codeArgument === [] || in_array($cacheCode, $codeArgument)) {
                    $enable[$cacheCode] = $status ? 1 : 0;
                }
            }
        } else {
            $enable = [];
        }

        Mage::app()->saveUseCache($enable);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateCacheCodes(array $codes): void
    {
        $cacheTypes = $this->_getCacheModel()->getTypes();
        foreach ($codes as $code) {
            if (!array_key_exists($code, $cacheTypes)) {
                throw new InvalidArgumentException('Invalid cache type: ' . $code);
            }
        }
    }

    /**
     * Ban cache usage before cleanup to get the latest values.
     *
     * @see https://github.com/netz98/n98-magerun/issues/483
     */
    protected function banUseCache(): void
    {
        if (!$this->_canUseBanCacheFunction()) {
            return;
        }

        $config = $this->getApplication()->getConfig();
        if (empty($config['init']['options'])) {
            $config['init']['options'] = ['global_ban_use_cache' => true];
            $this->getApplication()->setConfig($config);
        }
    }

    protected function reinitCache(): void
    {
        if (!$this->_canUseBanCacheFunction()) {
            return;
        }

        Mage::getConfig()->getOptions()->setData('global_ban_use_cache', false);
        Mage::getConfig()->reinit();
    }

    protected function _canUseBanCacheFunction(): bool
    {
        // @phpstan-ignore function.alreadyNarrowedType
        return method_exists('\Mage_Core_Model_App', 'baseInit');
    }
}
