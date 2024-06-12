<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Enterprise_PageCache_Model_Cache;
use InvalidArgumentException;
use Mage_Core_Model_Cache;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Zend_Cache_Core;

/**
 * Abstract cache command
 *
 * @package N98\Magento\Command\Cache
 */
abstract class AbstractCacheCommand extends AbstractMagentoCommand
{
    public const COMMAND_OPTION_FPC = 'fpc';

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
     * @param InputInterface $input
     * @return Zend_Cache_Core
     */
    protected function getCacheInstance(InputInterface $input): Zend_Cache_Core
    {
        if ($input->hasOption(static::COMMAND_OPTION_FPC) && $input->getOption(static::COMMAND_OPTION_FPC)) {
            if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                throw new RuntimeException('Enterprise page cache not found');
            }
            return Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
        }

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
