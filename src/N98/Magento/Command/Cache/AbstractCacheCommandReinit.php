<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract cache reinit class
 *
 * @package N98\Magento\Command\Cache
 */
abstract class AbstractCacheCommandReinit extends AbstractCacheCommand
{
    public const COMMAND_OPTION_REINIT = 'reinit';

    public const COMMAND_OPTION_NO_REINIT = 'no-reinit';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_REINIT,
                null,
                InputOption::VALUE_NONE,
                'Reinitialise the config cache after cleaning or flushing.'
            )
            ->addOption(
                self::COMMAND_OPTION_NO_REINIT,
                null,
                InputOption::VALUE_NONE,
                "Don't reinitialise the config cache after cleaning or flushing."
            );

        parent::configure();
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

        $this->_getMage()->getConfig()->getOptions()->setData('global_ban_use_cache', false);
        $this->_getMage()->getConfig()->reinit();
    }

    /**
     * @return bool
     */
    protected function _canUseBanCacheFunction(): bool
    {
        // @phpstan-ignore function.alreadyNarrowedType (Phpstan Bleeding edge only)
        return method_exists('\Mage_Core_Model_App', 'baseInit');
    }
}
