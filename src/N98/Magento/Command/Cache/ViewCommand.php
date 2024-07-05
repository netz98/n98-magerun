<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\MageMethods as Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * View cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ViewCommand extends AbstractCacheCommand
{
    public const COMMAND_ARGUMENT_ID = 'id';

    public const COMMAND_OPTION_UNSERIALZE = 'unserialize';

    /**
     * @var string
     */
    protected static $defaultName = 'cache:view';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Prints a cache entry.';

    protected function configure(): void
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
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @uses Mage::app()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $cacheId */
        $cacheId = $input->getArgument(self::COMMAND_ARGUMENT_ID);

        $cacheInstance = Mage::app()->getCacheInstance();
        $cacheData = $cacheInstance->load($cacheId);

        if (is_string($cacheData)) {
            if ($input->getOption(self::COMMAND_OPTION_UNSERIALZE)) {
                $cacheData = unserialize($cacheData);
                $cacheData = print_r($cacheData, true);
            }

            $output->writeln($cacheData);
        }

        return Command::SUCCESS;
    }
}
