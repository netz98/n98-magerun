<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlushCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:flush')
            ->setAliases(array('cache:clear'))
            ->addDeprecatedAlias('cache:clear', 'Please use cache:flush')
            ->setDescription('Flush magento cache storage')
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

            \Mage::app()->loadAreaPart('adminhtml');
            \Mage::dispatchEvent('adminhtml_cache_flush_all', array('output' => $output));
            \Mage::app()->getCacheInstance()->flush();
            $output->writeln('<info>Cache cleared</info>');

            if ($this->_magentoEnterprise) {
                \Enterprise_PageCache_Model_Cache::getCacheInstance()->flush();
                $output->writeln('<info>FPC cleared</info>');
            }

        }
    }
}