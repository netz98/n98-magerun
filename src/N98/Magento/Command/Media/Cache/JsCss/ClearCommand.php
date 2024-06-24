<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media\Cache\JsCss;

use Mage;
use N98\Magento\Command\AbstractCommand;
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
     * {@inheritDoc}
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Mage::getModel('core/design_package')->cleanMergedJsCss();
        Mage::dispatchEvent('clean_media_cache_after');
        $output->writeln('<info>Js/CSS cache cleared</info>');

        return Command::SUCCESS;
    }
}
