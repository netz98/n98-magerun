<?php

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List CMS block command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ListCommand extends AbstractMagentoCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('cms:block:list')
            ->setDescription('List all cms blocks')
            ->addFormatOption()
        ;
    }

    /**
     * Get an instance of cms/block
     *
     * @return \Mage_Cms_Model_Block
     */
    protected function _getBlockModel()
    {
        return $this->_getModel('cms/block', '\Mage_Cms_Model_Block');
    }

    /**
     * Execute the command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');

        /** @var \Mage_Cms_Model_Resource_Block $resourceModel */
        $resourceModel = $this->_getBlockModel()->getResource();

        $table = [];
        foreach ($cmsBlockCollection as $cmsBlock) {
            $storeIds = implode(',', $resourceModel->lookupStoreIds($cmsBlock->getId()));

            $table[] = [$cmsBlock->getData('block_id'), $cmsBlock->getData('identifier'), $cmsBlock->getData('title'), $cmsBlock->getData('is_active') ? 'active' : 'inactive', $storeIds];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['block_id', 'identifier', 'title', 'is_active', 'store_ids'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
