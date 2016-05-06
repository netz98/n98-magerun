<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use Varien_Db_Adapter_Interface;

class VersionCheck extends ResourceCheck
{
    /**
     * @param Result $result
     * @param Varien_Db_Adapter_Interface $dbAdapter
     * @return void
     */
    protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $dbAdapter)
    {
        /**
         * Check Version
         */
        $mysqlVersion = $dbAdapter->fetchOne('SELECT VERSION()');
        $minimumVersionFound = version_compare($mysqlVersion, '4.1.20', '>=');

        if ($minimumVersionFound) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage("<info>MySQL Version <comment>$mysqlVersion</comment> found.</info>");
        } else {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                "<error>MySQL Version <comment>>$mysqlVersion</comment> found. Upgrade your MySQL Version.</error>"
            );
        }
    }
}
