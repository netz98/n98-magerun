<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media\Cache\Image;

use Mage;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear media image cache command
 *
 * @package N98\Magento\Command\Media\Cache\Image
 */
class ClearCommand extends AbstractCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'media:cache:image:clear';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Clears image cache';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        Mage::getModel('catalog/product_image')->clearCache();
        Mage::dispatchEvent('clean_catalog_images_cache_after');
        $output->writeln('<info>Image cache cleared</info>');

        return Command::SUCCESS;
    }
}
