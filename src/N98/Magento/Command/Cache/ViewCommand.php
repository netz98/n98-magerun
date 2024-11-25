<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Mage;
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
    protected function configure(): void
    {
        $this
            ->setName('cache:view')
            ->addArgument('id', InputArgument::REQUIRED, 'Cache-ID')
            ->addOption('unserialize', '', InputOption::VALUE_NONE, 'Unserialize output')
            ->setDescription('Prints a cache entry')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $cacheInstance = Mage::app()->getCache();
        $cacheData = $cacheInstance->load($input->getArgument('id'));
        if ($input->getOption('unserialize')) {
            $cacheData = unserialize($cacheData);
            $cacheData = print_r($cacheData, true);
        }

        $output->writeln($cacheData);
        return Command::SUCCESS;
    }
}
