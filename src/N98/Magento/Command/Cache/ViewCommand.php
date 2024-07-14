<?php

namespace N98\Magento\Command\Cache;

use Enterprise_PageCache_Model_Cache;
use Mage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\RuntimeException;

/**
 * View cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ViewCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:view')
            ->addArgument('id', InputArgument::REQUIRED, 'Cache-ID')
            ->addOption('unserialize', '', InputOption::VALUE_NONE, 'Unserialize output')
            ->setDescription('Prints a cache entry')
            ->addOption(
                'fpc',
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
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        if ($input->hasOption('fpc') && $input->getOption('fpc')) {
            if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                throw new RuntimeException('Enterprise page cache not found');
            }
            $cacheInstance = Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
        } else {
            $cacheInstance = Mage::app()->getCache();
        }
        /* @var \Varien_Cache_Core $cacheInstance */
        $cacheData = $cacheInstance->load($input->getArgument('id'));
        if ($input->getOption('unserialize')) {
            $cacheData = unserialize($cacheData);
            $cacheData = print_r($cacheData, true);
        }

        $output->writeln($cacheData);
        return 0;
    }
}
