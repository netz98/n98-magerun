<?php

namespace N98\Magento\Command\System\Check\PHP;

use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

class MemorylimitCheck implements SimpleCheck, CommandConfigAware
{
    /**
     * @var array
     */
    protected $_commandConfig;

    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        $memoryLimit = ini_get('memory_limit');

        $result = $results->createResult();
        $result->setStatus($memoryLimit != -1 ? Result::STATUS_OK : Result::STATUS_ERROR);
        if ($result->isValid()) {
            $result->setMessage("<info>CLI Memory Limit (Magento Bug) <comment>$memoryLimit</comment>.</info>");
        } else {
            $result->setMessage("<error>CLI Memory Limit (Magento Bug) is -1</error> <comment>It's not recommended to use memory_limit -1 due to a Magento bug in Gd2.php.</comment>");
        }

        $recommendMemoryLimitInMBytes = $this->_commandConfig['php']['recommend-memory-limit'];
        $memoryLimitInMBytes = $this->_convertMemoryLimitToMByte($memoryLimit);
        $result = $results->createResult();
        $result->setStatus(($memoryLimitInMBytes >= $recommendMemoryLimitInMBytes) ? Result::STATUS_OK : Result::STATUS_ERROR);
        if ($result->isValid()) {
            $result->setMessage("<info>CLI Memory Limit <comment>$memoryLimit</comment>.</info>");
        } else {
            $result->setMessage("<error>CLI Memory Limit is smaller than recommended</error> <comment>Recommended limit is $recommendMemoryLimitInMBytes MB.</comment>");
        }
    }

    /**
     * @param array $commandConfig
     */
    public function setCommandConfig(array $commandConfig)
    {
        $this->_commandConfig = $commandConfig;
    }

    /**
     * @param string $memoryValue
     *
     * @return int
     */
    protected function _convertMemoryLimitToMByte($memoryValue)
    {
        if (stripos($memoryValue, 'M') !== false) {
            return (int)$memoryValue;
        } elseif (stripos($memoryValue, 'KB') !== false) {
            return (int)$memoryValue / 1024;
        } elseif (stripos($memoryValue, 'G') !== false) {
            return (int)$memoryValue * 1024;
        } elseif ($memoryValue == -1) {
            return 99999;
        }

        return (int)$memoryValue;
    }

}
