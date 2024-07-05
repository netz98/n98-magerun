<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use N98\Magento\Methods\Core;
use Varien_Db_Adapter_Interface;

/**
 * @package N98\Magento\Command\System\Check\MySQL
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
abstract class ResourceCheck implements SimpleCheck
{
    /**
     * @param ResultCollection $results
     *
     * @uses Core\Resource::getModel()
     */
    public function check(ResultCollection $results): void
    {
        $resourceModel = Core\Resource::getModel();

        /** @var Varien_Db_Adapter_Interface|false $dbAdapter */
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
    abstract protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $dbAdapter): void;
}
