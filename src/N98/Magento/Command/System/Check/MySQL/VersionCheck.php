<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use Varien_Db_Adapter_Interface;

/**
 * Class VersionCheck
 *
 * @package N98\Magento\Command\System\Check\MySQL
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class VersionCheck extends ResourceCheck
{
    protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $varienDbAdapter): void
    {
        /**
         * Check Version
         */
        $mysqlVersion = $varienDbAdapter->fetchOne('SELECT VERSION()');
        $minimumVersionFound = version_compare($mysqlVersion, '4.1.20', '>=');

        if ($minimumVersionFound) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage(sprintf('<info>MySQL Version <comment>%s</comment> found.</info>', $mysqlVersion));
        } else {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                sprintf('<error>MySQL Version <comment>>%s</comment> found. Upgrade your MySQL Version.</error>', $mysqlVersion),
            );
        }
    }
}
