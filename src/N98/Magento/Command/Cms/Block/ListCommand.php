<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List CMS block command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ListCommand extends AbstractMagentoCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'cms:block:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'List all cms blocks.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'CMS blocks';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['block_id', 'identifier', 'title', 'is_active', 'store_ids'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');
        $resourceModel = $this->_getBlockModel()->getResource();

        $table = [];
        foreach ($cmsBlockCollection as $cmsBlock) {
            $storeIds = implode(',', $resourceModel->lookupStoreIds($cmsBlock->getId()));

            $table[] = [
                $cmsBlock->getData('block_id'),
                $cmsBlock->getData('identifier'),
                $cmsBlock->getData('title'),
                $cmsBlock->getData('is_active') ? 'active' : 'inactive', $storeIds
            ];
        }

        return $table;
    }

    /**
     * Get an instance of cms/block
     *
     * @return \Mage_Cms_Model_Block
     */
    protected function _getBlockModel()
    {
        return $this->_getModel('cms/block');
    }
}
