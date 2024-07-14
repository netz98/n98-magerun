<?php

namespace N98\Magento\Command\Media\Cache\JsCss;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear JS/CSS cache command
 *
 * @package N98\Magento\Command\Media\Cache\JsCss
 */
class ClearCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this->setName('media:cache:jscss:clear')
             ->setDescription('Clears JS/CSS cache');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        if ($this->initMagento()) {
            Mage::getModel('core/design_package')->cleanMergedJsCss();
            Mage::dispatchEvent('clean_media_cache_after');
            $output->writeln('<info>Js/CSS cache cleared</info>');
        }
        return 0;
    }
}
