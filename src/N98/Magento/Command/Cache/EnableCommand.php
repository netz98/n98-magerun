<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:enable')
            ->setDescription('Enables magento caches')
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
        if ($this->initMagento($output)) {
            $cacheTypes = array_keys($this->getCoreHelper()->getCacheTypes());
            $enable = array();
            foreach ($cacheTypes as $type) {
                $enable[$type] = 1;
            }

            \Mage::app()->saveUseCache($enable);
            $this->_getCacheModel()->flush();

            $output->writeln('<info>Caches enabled</info>');
        }
    }
}