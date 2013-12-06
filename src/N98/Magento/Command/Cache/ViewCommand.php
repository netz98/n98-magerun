<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ViewCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:view')
            ->addArgument('id', InputArgument::REQUIRED, 'Cache-ID')
            ->setDescription('Prints a cache entry')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $cacheInstance = \Mage::app()->getCache();
            /* @var $cacheInstance \Varien_Cache_Core */
            $output->writeln($cacheInstance->load($input->getArgument('id')));
        }
    }
}