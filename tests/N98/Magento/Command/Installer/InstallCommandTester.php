<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Installer;

/**
 * InstallCommandTester with public getter for InstallCommand command config's packages
 *
 * @package N98\Magento\Command\Installer
 */
class InstallCommandTester extends InstallCommand
{
    public const COMMAND_CLASS = 'N98\Magento\Command\Installer\InstallCommand';

    /**
     * @return array
     */
    public function getMagentoPackages(InstallCommand $installCommand)
    {
        $commandClass = self::COMMAND_CLASS;
        $commandConfig = $installCommand->getCommandConfig($commandClass);
        return $commandConfig['magento-packages'];
    }

    /**
     * @return array
     */
    public function getSampleDataPackages(InstallCommand $installCommand)
    {
        $commandClass = self::COMMAND_CLASS;
        $commandConfig = $installCommand->getCommandConfig($commandClass);
        return $commandConfig['demo-data-packages'];
    }
}
