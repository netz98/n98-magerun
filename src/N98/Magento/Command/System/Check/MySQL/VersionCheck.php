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

        /** @var $resourceModel \Mage_Core_Model_Resource */
        $resourceModel = \Mage::getModel('core/resource');

        /** @var $dbAdapter \Varien_Db_Adapter_Interface|false */
        $dbAdapter = $resourceModel->getConnection('core_write');

        if (!$dbAdapter instanceof \Varien_Db_Adapter_Interface) {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                "<error>Mysql Version: Can not check. Unable to obtain resource connection 'core_write'.</error>"
            );
            return;
        }

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
