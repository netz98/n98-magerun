<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->addArgument('type', InputArgument::OPTIONAL, 'Cache type code like "config"')
            ->setAliases(array('cache:flush'))
            ->setDescription('Clear magento cache')
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
            if ($input->getArgument('type') != '') {
                if ($this->_getCacheModel()->cleanType($input->getArgument('type'))) {
                    \Mage::getConfig()->loadEventObservers('adminhtml');
                    \Mage::app()->addEventArea('adminhtml');
                    \Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $input->getArgument('type')));
                    $output->writeln('<info>' . $input->getArgument('type') . ' cache cleared</info>');
                }
            } else {
                if ($this->_getCacheModel()->flush()) {
                    $output->writeln('<info>Core Cache cleared</info>');
                }
                if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_1
                    && is_callable(array('\Enterprise_PageCache_Model_Cache', 'getCacheInstance'))
                ) {
                    $cacheInstance = \Enterprise_PageCache_Model_Cache::getCacheInstance();
                    $cacheInstance->flush();
                    $output->writeln('<info>Fullpage Cache cleared</info>');
                }
            }
        }
    }
}