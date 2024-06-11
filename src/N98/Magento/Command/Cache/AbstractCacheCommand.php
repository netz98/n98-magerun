<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Enterprise_PageCache_Model_Cache;
use InvalidArgumentException;
use Mage_Core_Model_Cache;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Zend_Cache_Core;

/**
 * Abstract cache command
 *
 * @package N98\Magento\Command\Cache
 */
class AbstractCacheCommand extends AbstractMagentoCommand
{
    public const COMMAND_ARGUMENT_CODE = 'code';

    public const COMMAND_OPTION_FPC = 'fpc';

    protected function configure()
    {
        $this->addArgument(
            self::COMMAND_ARGUMENT_CODE,
            InputArgument::OPTIONAL,
            'Code of cache (Multiple codes operated by comma)'
        );

        parent::configure();
    }

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
     * @param array $codeArgument
     * @param bool $status
     */
    protected function saveCacheStatus(array $codeArgument, bool $status)
    {
        $this->validateCacheCodes($codeArgument);

        $cacheTypes = $this->_getCacheModel()->getTypes();
        $enable = $this->_getMage()->useCache();
        foreach ($cacheTypes as $cacheCode => $cacheModel) {
            if (empty($codeArgument) || in_array($cacheCode, $codeArgument)) {
                $enable[$cacheCode] = $status ? 1 : 0;
            }
        }

        $this->_getMage()->saveUseCache($enable);
    }

    /**
     * @param array $codes
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
            $config['init']['options'] = ['global_ban_use_cache' => true];
            $this->getApplication()->setConfig($config);
        }
    }

    protected function reinitCache()
    {
        if (!$this->_canUseBanCacheFunction()) {
            return;
        }

        $this->_getMage()->getConfig()->getOptions()->setData('global_ban_use_cache', false);
        $this->_getMage()->getConfig()->reinit();
    }

    /**
     * @return bool
     */
    protected function _canUseBanCacheFunction(): bool
    {
        return method_exists('\Mage_Core_Model_App', 'baseInit');
    }
}
