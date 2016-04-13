<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\MySQL;

use Mage;
use Mage_Core_Model_Resource;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use Varien_Db_Adapter_Interface;

abstract class ResourceCheck implements SimpleCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        /** @var $resourceModel Mage_Core_Model_Resource */
        $resourceModel = Mage::getModel('core/resource');

        /** @var $dbAdapter Varien_Db_Adapter_Interface|false */
        $dbAdapter = $resourceModel->getConnection('core_write');

        $result = $results->createResult();

        if (!$dbAdapter instanceof Varien_Db_Adapter_Interface) {
            $result->setStatus($result::STATUS_ERROR);
            $result->setMessage(
                "<error>Mysql Version: Can not check. Unable to obtain resource connection 'core_write'.</error>"
            );
        } else {
            $this->checkImplementation($result, $dbAdapter);
        }
    }

    /**
     * @param Result $result
     * @param Varien_Db_Adapter_Interface $dbAdapter
     * @return void
     */
    abstract protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $dbAdapter);
}
