<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage_Cms_Model_Block;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List CMS block command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cms:block:list')
            ->setDescription('List all cms blocks')
            ->addFormatOption()
        ;
    }

    /**
     * Get an instance of cms/block
     */
    protected function _getBlockModel(): Mage_Cms_Model_Block
    {
        /** @var Mage_Cms_Model_Block $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('cms/block');
        return $mageCoreModelAbstract;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');

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

        return Command::SUCCESS;
    }
}
