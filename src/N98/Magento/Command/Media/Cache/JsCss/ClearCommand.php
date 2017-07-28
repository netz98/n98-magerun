<?php

namespace N98\Magento\Command\Media\Cache\JsCss;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this->setName('media:cache:jscss:clear')
             ->setDescription('Clears JS/CSS cache');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);

        if ($this->initMagento()) {
            \Mage::getModel('core/design_package')->cleanMergedJsCss();
            \Mage::dispatchEvent('clean_media_cache_after');
            $output->writeln('<info>Js/CSS cache cleared</info>');
        }
    }
}
