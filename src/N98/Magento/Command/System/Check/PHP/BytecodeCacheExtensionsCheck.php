<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\PHP;

use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

/**
 * Class BytecodeCacheExtensionsCheck
 *
 * @package N98\Magento\Command\System\Check\PHP
 */
class BytecodeCacheExtensionsCheck implements SimpleCheck, CommandConfigAware
{
    protected array $_commandConfig;

    public function check(ResultCollection $resultCollection): void
    {
        $result = $resultCollection->createResult();

        $bytecopdeCacheExtensions = $this->_commandConfig['php']['bytecode-cache-extensions'];
        $bytecodeCacheExtensionLoaded = false;
        $bytecodeCacheExtension = null;
        foreach ($bytecopdeCacheExtensions as $bytecopdeCacheExtension) {
            if (extension_loaded($bytecopdeCacheExtension)) {
                $bytecodeCacheExtension = $bytecopdeCacheExtension;
                $bytecodeCacheExtensionLoaded = true;
                break;
            }
        }

        $result->setStatus($bytecodeCacheExtensionLoaded ? Result::STATUS_OK : Result::STATUS_WARNING);
        if ($result->isValid()) {
            $result->setMessage(sprintf('<info>Bytecode Cache <comment>%s</comment> found.</info>', $bytecodeCacheExtension));
        } else {
            $result->setMessage(
                "<error>No Bytecode-Cache found!</error> <comment>It's recommended to install anyone of " .
                implode(', ', $bytecopdeCacheExtensions) . '.</comment>',
            );
        }
    }

    public function setCommandConfig(array $commandConfig): void
    {
        $this->_commandConfig = $commandConfig;
    }
}
