<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractMviewIndexerCommand
 *
 * @package N98\Magento\Command\Indexer
 */
class AbstractMviewIndexerCommand extends AbstractMagentoCommand
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }

    /**
     * @return \Enterprise_Mview_Model_Resource_Metadata_Collection
     */
    public function getMetaDataCollection()
    {
        return $this->_getModel('enterprise_mview/metadata')->getCollection();
    }

    /**
     * @return array[]
     */
    protected function getIndexers()
    {
        /** @var \Enterprise_Index_Helper_Data $helper */
        $helper = $this->_getHelper('enterprise_index');

        $indexers = [];
        foreach ($helper->getIndexers(true) as $indexer) {
            $indexers[(string) $indexer->index_table] = $indexer;
        }

        foreach ($indexers as $indexerKey => $indexerData) {
            if (!isset($indexerData->action_model->changelog)) {
                unset($indexers[$indexerKey]);
            }
        }

        return $indexers;
    }

    /**
     * @return \Enterprise_Mview_Model_Client
     */
    protected function getMviewClient()
    {
        return $this->_getModel('enterprise_mview/client');
    }
}
