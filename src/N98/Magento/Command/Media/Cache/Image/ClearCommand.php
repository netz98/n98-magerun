<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media\Cache\Image;

use Mage;
use Mage_Catalog_Model_Product_Image;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear image cache command
 *
 * @package N98\Magento\Command\Media\Cache\Image
 */
class ClearCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this->setName('media:cache:image:clear')
             ->setDescription('Clears image cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        if ($this->initMagento()) {
            /** @var Mage_Catalog_Model_Product_Image $model */
            $model = Mage::getModel('catalog/product_image');
            $model->clearCache();
            Mage::dispatchEvent('clean_catalog_images_cache_after');
            $output->writeln('<info>Image cache cleared</info>');
        }

        return Command::SUCCESS;
    }
}
