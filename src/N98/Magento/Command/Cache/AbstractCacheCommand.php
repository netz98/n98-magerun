<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use InvalidArgumentException;
use Mage_Core_Model_Cache;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Zend_Cache_Core;

/**
 * Abstract cache command
 *
 * @package N98\Magento\Command\Cache
 */
abstract class AbstractCacheCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Cache
     *
     * @throws RuntimeException
     */
    protected function _getCacheModel(): Mage_Core_Model_Cache
    {
        return $this->_getMage()->getCacheInstance();
    }

    /**
     * @return Zend_Cache_Core
     */
    protected function getCacheInstance(): Zend_Cache_Core
    {
        return $this->_getMage()->getCache();
    }

    /**
     * @param string[] $codes
     * @throws InvalidArgumentException
     */
    protected function validateCacheCodes(array $codes): void
    {
        $cacheTypes = $this->_getCacheModel()->getTypes();
        foreach ($codes as $cacheCode) {
            if (!array_key_exists($cacheCode, $cacheTypes)) {
                throw new InvalidArgumentException('Invalid cache type: ' . $cacheCode);
            }
        }
    }
}
