<?php
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
    /**
     * @param InstallCommand $command
     * @return array
     */
    public function getMagentoPackages(InstallCommand $command)
    {
        $commandClass = 'N98\Magento\Command\Installer\InstallCommand';
        $commandConfig = $command->getCommandConfig($commandClass);
        return $commandConfig['magento-packages'];
    }
}
