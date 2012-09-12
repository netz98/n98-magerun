<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clean')
            ->addArgument('type', InputArgument::OPTIONAL, 'Cache type code like "config"')
            ->setDescription('Clean magento cache')
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
            $allTypes = \Mage::app()->useCache();
            foreach(array_keys($allTypes) as $type) {
                if ($input->getArgument('type') == '' || $input->getArgument('type') == $type) {
                    \Mage::app()->getCacheInstance()->cleanType($type);
                    $output->writeln('<info>' . $type . ' cache cleared</info>');
                }
            }
        }
    }
}