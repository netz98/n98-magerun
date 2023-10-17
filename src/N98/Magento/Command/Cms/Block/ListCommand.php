<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage_Cms_Model_Block;
use Mage_Core_Exception;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List CMS blocks command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ListCommand extends AbstractCmsBlockCommand implements AbstractMagentoCommandFormatInterface
{
    /**
     * @var array<int, array<string, int|string>>|null
     */
    private ?array $data = null;

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cms:block:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'List all cms blocks';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array<int, array<string, int|string>>
     * @throws Mage_Core_Exception
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            /** @var Mage_Cms_Model_Block[] $cmsBlockCollection */
            $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');

            $resourceModel = $this->_getBlockModel()->getResource();

            $this->data = [];
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

        return $this->data;
    }
}
