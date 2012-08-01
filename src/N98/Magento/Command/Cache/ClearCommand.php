<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
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
            if (\Mage::app()->getCache()->clean()) {
                $output->writeln('<info>Cache cleared</info>');
            }
        }
    }
}