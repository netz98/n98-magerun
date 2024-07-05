<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media\Cache\JsCss;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Methods\Core\Design;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear CSS/JS cache command
 *
 * @package N98\Magento\Command\Media\Cache\JsCss
 */
class ClearCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'media:cache:jscss:clear';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Clears JS/CSS cache.';

    /**
     * {@inheritdoc}
     *
     * @uses Design\Package::getModel()
     * @uses Mage::dispatchEvent()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Design\Package::getModel()->cleanMergedJsCss();
        Mage::dispatchEvent('clean_media_cache_after');
        $output->writeln('<info>Js/CSS cache cleared</info>');

        return Command::SUCCESS;
    }
}
