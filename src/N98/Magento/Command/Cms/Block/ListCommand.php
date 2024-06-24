<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage_Cms_Model_Block;
use Mage_Core_Exception;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List CMS blocks command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ListCommand extends AbstractCmsBlockCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'CMS blocks';

    /**
     * @var string
     */
    protected static $defaultName = 'cms:block:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List all CMS blocks.';

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Exception
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];

        /** @var Mage_Cms_Model_Block[] $cmsBlockCollection */
        $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');
        $resourceModel = $this->_getBlockModel()->getResource();
        foreach ($cmsBlockCollection as $cmsBlock) {
            $storeIds = implode(',', $resourceModel->lookupStoreIds((int)$cmsBlock->getId()));

            $this->data[] = [
                'block_id'      => $cmsBlock->getBlockId(),
                'title'         => $cmsBlock->getTitle(),
                'identifier'    => $cmsBlock->getIdentifier(),
                'is_active'     => $cmsBlock->getIsActive() ? 'active' : 'inactive',
                'store_ids'     => $storeIds
            ];
        }
    }
}
