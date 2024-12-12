<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\MySQL;

use N98\Magento\Command\System\Check\Result;
use Varien_Db_Adapter_Interface;

/**
 * Class EnginesCheck
 *
 * @package N98\Magento\Command\System\Check\MySQL
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class EnginesCheck extends ResourceCheck
{
    protected function checkImplementation(Result $result, Varien_Db_Adapter_Interface $varienDbAdapter): void
    {
        $innodbFound = $this->checkInnodbEngine($varienDbAdapter);

        if ($innodbFound) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage('<info>Required MySQL Storage Engine <comment>InnoDB</comment> found.</info>');
        } else {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(
                '<error>Required MySQL Storage Engine <comment>InnoDB</comment> not found!</error>',
            );
        }
    }

    private function checkInnodbEngine(Varien_Db_Adapter_Interface $varienDbAdapter): bool
    {
        $innodbFound = false;

        $engines = $varienDbAdapter->fetchAll('SHOW ENGINES');
        foreach ($engines as $engine) {
            if (strtolower($engine['Engine']) === 'innodb') {
                $innodbFound = true;
                break;
            }
        }

        return $innodbFound;
    }
}
