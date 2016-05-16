<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\RuntimeException;

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
     * @return int|void
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        if ($input->hasOption('fpc') && $input->getOption('fpc')) {
            if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                throw new RuntimeException('Enterprise page cache not found');
            }
            $cacheInstance = \Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
        } else {
            $cacheInstance = \Mage::app()->getCache();
        }
        /* @var $cacheInstance \Varien_Cache_Core */
        $cacheData = $cacheInstance->load($input->getArgument('id'));
        if ($input->getOption('unserialize')) {
            $cacheData = unserialize($cacheData);
            $cacheData = print_r($cacheData, true);
        }

        $output->writeln($cacheData);
    }
}
