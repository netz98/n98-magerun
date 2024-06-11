<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Enterprise_PageCache_Model_Cache;
use Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\RuntimeException;
use Varien_Cache_Core;

/**
 * View cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ViewCommand extends AbstractCacheCommand
{
    public const COMMAND_ARGUMENT_ID = 'id';

    public const COMMAND_OPTION_UNSERIALZE = 'id';

    public const COMMAND_OPTION_FPC = 'id';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:view';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Prints a cache entry.';

    protected function configure()
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ID,
                InputArgument::REQUIRED,
                'Cache-ID'
            )
            ->addOption(
                self::COMMAND_OPTION_UNSERIALZE,
                null,
                InputOption::VALUE_NONE,
                'Unserialize output'
            )
            ->addOption(
                self::COMMAND_OPTION_FPC,
                null,
                InputOption::VALUE_NONE,
                'Use full page cache instead of core cache (Enterprise only!)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        if ($input->hasOption(self::COMMAND_OPTION_FPC) && $input->getOption(self::COMMAND_OPTION_FPC)) {
            if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                throw new RuntimeException('Enterprise page cache not found');
            }
            $cacheInstance = Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
        } else {
            $cacheInstance = Mage::app()->getCache();
        }
        /** @var Varien_Cache_Core $cacheInstance */
        $cacheData = $cacheInstance->load($input->getArgument(self::COMMAND_ARGUMENT_ID));
        if ($input->getOption(self::COMMAND_OPTION_UNSERIALZE)) {
            $cacheData = unserialize($cacheData);
            $cacheData = print_r($cacheData, true);
        }

        $output->writeln($cacheData);

        return Command::SUCCESS;
    }
}
