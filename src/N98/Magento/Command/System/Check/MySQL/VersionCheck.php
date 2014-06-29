<?php

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

class VersionCheck implements SimpleCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        $result = $results->createResult();
        $dbAdapter = \Mage::getModel('core/resource')->getConnection('core_write');

        /**
         * Check Version
         */
        $mysqlVersion = $dbAdapter->fetchOne('SELECT VERSION()');
        if (version_compare($mysqlVersion, '4.1.20', '>=')) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage("<info>MySQL Version <comment>$mysqlVersion</comment> found.</info>");
        } else {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage("<error>MySQL Version $mysqlVersion found. Upgrade your MySQL Version.</error>");
        }
    }
}