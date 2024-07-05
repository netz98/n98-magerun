<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage_Cms_Model_Block;
use Mage_Core_Exception;
use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\Cms\Block;
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
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['block_id', 'title', 'identifier', 'is_active', 'store_ids'];
    }

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Exception
     * @uses Block::getModel()
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            /** @var Mage_Cms_Model_Block[] $cmsBlockCollection */
            $cmsBlockCollection = Block::getModel()->getCollection()->addFieldToSelect('*');
            $resourceModel = Block::getModel()->getResource();
            foreach ($cmsBlockCollection as $cmsBlock) {
                $storeIds = implode(',', $resourceModel->lookupStoreIds((int)$cmsBlock->getId()));

                $this->data[] = [
                    $cmsBlock->getBlockId(),
                    $cmsBlock->getTitle(),
                    $cmsBlock->getIdentifier(),
                    $cmsBlock->getIsActive() ? 'active' : 'inactive',
                    $storeIds
                ];
            }
        }

        return $this->data;
    }
}
