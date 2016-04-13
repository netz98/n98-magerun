<?php

namespace N98\Magento\Command\System\Check\MySQL;

use Mage;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

class EnginesCheck implements SimpleCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        $result = $results->createResult();

        /** @var $resourceModel \Mage_Core_Model_Resource */
        $resourceModel = Mage::getModel('core/resource');

        /** @var $dbAdapter \Varien_Db_Adapter_Interface|false */
        $dbAdapter = $resourceModel->getConnection('core_write');

        $engines = $dbAdapter->fetchAll('SHOW ENGINES');
        $innodbFound = false;
        foreach ($engines as $engine) {
            if (strtolower($engine['Engine']) == 'innodb') {
                $innodbFound = true;
                break;
            }
        }

        $result->setStatus($innodbFound ? Result::STATUS_OK : Result::STATUS_ERROR);

        if ($innodbFound) {
            $result->setMessage("<info>Required MySQL Storage Engine <comment>InnoDB</comment> found.</info>");
        } else {
            $result->setMessage("<error>Required MySQL Storage Engine \"InnoDB\" not found!</error>");
        }
    }
}
