<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractIndexerCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexerModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Index_Model_Indexer');
        } else {
            return \Mage::getModel('index/indexer');
        }
    }

    /**
     * @return array
     */
    protected function getIndexerList()
    {
        $list = array();
        $indexCollection = $this->_getIndexerModel()->getProcessesCollection();
        foreach ($indexCollection as $indexer) {
            $list[] = array(
                'code'   => $indexer->getIndexerCode(),
                'status' => $indexer->getStatus()
            );
        }

        return $list;
    }
}
