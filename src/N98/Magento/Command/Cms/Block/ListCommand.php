<?php

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CMS Block ListCommand
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
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
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
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $cmsBlockCollection = $this->_getBlockModel()->getCollection()->addFieldToSelect('*');

        /** @var \Mage_Cms_Model_Resource_Block $resourceModel */
        $resourceModel = $this->_getBlockModel()->getResource();

        $table = array();
        foreach ($cmsBlockCollection as $cmsBlock) {
            $storeIds = implode(',', $resourceModel->lookupStoreIds($cmsBlock->getId()));

            $table[] = array(
                $cmsBlock->getData('block_id'),
                $cmsBlock->getData('identifier'),
                $cmsBlock->getData('title'),
                $cmsBlock->getData('is_active') ? 'active' : 'inactive',
                $storeIds,
            );
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('block_id', 'identifier', 'title', 'is_active', 'store_ids'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
