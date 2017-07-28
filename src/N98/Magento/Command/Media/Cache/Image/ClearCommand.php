<?php

namespace N98\Magento\Command\Media\Cache\Image;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this->setName('media:cache:image:clear')
             ->setDescription('Clears image cache');
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
            \Mage::getModel('catalog/product_image')->clearCache();
            \Mage::dispatchEvent('clean_catalog_images_cache_after');
            $output->writeln('<info>Image cache cleared</info>');
        }
    }
}
