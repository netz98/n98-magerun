<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use Varien_Db_Adapter_Interface;

class EnginesCheck extends ResourceCheck
{
    /**
     * @param Result $result
     * @param Varien_Db_Adapter_Interface $dbAdapter
     * @return void
     */
    protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $dbAdapter)
    {
        $innodbFound = $this->checkInnodbEngine($dbAdapter);

        if ($innodbFound) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage("<info>Required MySQL Storage Engine <comment>InnoDB</comment> found.</info>");
        } else {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                "<error>Required MySQL Storage Engine <comment>InnoDB</comment> not found!</error>"
            );
        }
    }

    /**
     * @param Varien_Db_Adapter_Interface $dbAdapter
     * @return bool
     */
    private function checkInnodbEngine(Varien_Db_Adapter_Interface $dbAdapter)
    {
        $innodbFound = false;

        $engines = $dbAdapter->fetchAll('SHOW ENGINES');

        foreach ($engines as $engine) {
            if (strtolower($engine['Engine']) === 'innodb') {
                $innodbFound = true;
                break;
            }
        }

        return $innodbFound;
    }
}
