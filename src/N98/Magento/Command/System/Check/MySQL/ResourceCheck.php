<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\MySQL;

use Mage;
use Mage_Core_Model_Resource;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use Varien_Db_Adapter_Interface;

/**
 * Class ResourceCheck
 *
 * @package N98\Magento\Command\System\Check\MySQL
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
abstract class ResourceCheck implements SimpleCheck
{
    public function check(ResultCollection $resultCollection): void
    {
        /** @var Mage_Core_Model_Resource $resourceModel */
        $resourceModel = Mage::getModel('core/resource');

        /** @var Varien_Db_Adapter_Interface|false $dbAdapter */
        $dbAdapter = $resourceModel->getConnection('core_write');

        $result = $resultCollection->createResult();

        if (!$dbAdapter instanceof Varien_Db_Adapter_Interface) {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                "<error>Mysql Version: Can not check. Unable to obtain resource connection 'core_write'.</error>",
            );
        } else {
            $this->checkImplementation($result, $dbAdapter);
        }
    }

    abstract protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $varienDbAdapter): void;
}
