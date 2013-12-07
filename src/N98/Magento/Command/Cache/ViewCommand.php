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
            ->setDescription('Prints a cache entry')
            ->addOption('fpc', null, InputOption::VALUE_NONE, 'Use full page cache instead of core cache (Enterprise only!)');
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            if ($input->hasOption('fpc') && $input->getOption('fpc')) {
                if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                    throw new \RuntimeException('Enterprise page cache not found');
                }
                $cacheInstance = \Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
            } else {
                $cacheInstance = \Mage::app()->getCache();
            }
            /* @var $cacheInstance \Varien_Cache_Core */
            $output->writeln($cacheInstance->load($input->getArgument('id')));
        }
    }
}