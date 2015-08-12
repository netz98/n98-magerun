<?php

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\SimpleCheck;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;

class EnginesCheck implements SimpleCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        $result = $results->createResult();
        $dbAdapter = \Mage::getModel('core/resource')->getConnection('core_write');

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