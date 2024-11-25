<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media\Cache\JsCss;

use Mage;
use Mage_Core_Model_Design_Package;
use MagentoHackathon\Composer\Magento\Deploystrategy\Move;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear JS/CSS cache command
 *
 * @package N98\Magento\Command\Media\Cache\JsCss
 */
class ClearCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this->setName('media:cache:jscss:clear')
             ->setDescription('Clears JS/CSS cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        if ($this->initMagento()) {
            /** @var Mage_Core_Model_Design_Package $model */
            $model = Mage::getModel('core/design_package');
            $model->cleanMergedJsCss();
            Mage::dispatchEvent('clean_media_cache_after');
            $output->writeln('<info>Js/CSS cache cleared</info>');
        }

        return Command::SUCCESS;
    }
}
