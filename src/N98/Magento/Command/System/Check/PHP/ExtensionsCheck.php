<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\PHP;

use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

/**
 * Class ExtensionsCheck
 *
 * @package N98\Magento\Command\System\Check\PHP
 */
class ExtensionsCheck implements SimpleCheck, CommandConfigAware
{
    protected array $_commandConfig;

    public function check(ResultCollection $resultCollection): void
    {
        $requiredExtensions = $this->_commandConfig['php']['required-extensions'];

        foreach ($requiredExtensions as $requiredExtension) {
            $result = $resultCollection->createResult();
            $result->setStatus(extension_loaded($requiredExtension) ? Result::STATUS_OK : Result::STATUS_ERROR);
            if ($result->isValid()) {
                $result->setMessage(sprintf('<info>Required PHP Module <comment>%s</comment> found.</info>', $requiredExtension));
            } else {
                $result->setMessage(sprintf('<error>Required PHP Module %s not found!</error>', $requiredExtension));
            }
        }
    }

    public function setCommandConfig(array $commandConfig): void
    {
        $this->_commandConfig = $commandConfig;
    }
}
