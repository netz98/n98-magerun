<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\DateTime as DateTimeUtils;

class AbstractIndexerCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexerModel()
    {
        return $this->_getModel('index/indexer', 'Mage_Index_Model_Indexer');
    }

    /**
     * @return array
     */
    protected function getIndexerList()
    {
        $list = array();
        $indexCollection = $this->_getIndexerModel()->getProcessesCollection();
        foreach ($indexCollection as $indexer) {
            $lastReadbleRuntime = $this->getRuntime($indexer);
            $runtimeInSeconds = $this->getRuntimeInSeconds($indexer);
            $list[] = array(
                'code'            => $indexer->getIndexerCode(),
                'status'          => $indexer->getStatus(),
                'last_runtime'    => $lastReadbleRuntime,
                'runtime_seconds' => $runtimeInSeconds,
            );
        }

        return $list;
    }

    /**
     * Returns a readable runtime
     *
     * @param $indexer
     * @return mixed
     */
    protected function getRuntime($indexer)
    {
        $dateTimeUtils = new DateTimeUtils();
        $startTime = new \DateTime($indexer->getStartedAt());
        $endTime = new \DateTime($indexer->getEndedAt());
        if ($startTime > $endTime) {
            return 'index not finished';
        }
        $lastRuntime = $dateTimeUtils->getDifferenceAsString($startTime, $endTime);
        return $lastRuntime;
    }

    /**
     * Disable observer which try to create adminhtml session on CLI
     */
    protected function disableObservers()
    {
        $node = \Mage::app()->getConfig()->getNode('adminhtml/events/core_locale_set_locale/observers/bind_locale');
        if ($node) {
            $node->appendChild(new \Varien_Simplexml_Element('<type>disabled</type>'));
        }
    }

    /**
     * Returns the runtime in total seconds
     *
     * @param $indexer
     * @return int
     */
    protected function getRuntimeInSeconds($indexer)
    {
        $startTimestamp = strtotime($indexer->getStartedAt());
        $endTimestamp = strtotime($indexer->getEndedAt());

        return $endTimestamp - $startTimestamp;
    }
}
