<?php

namespace N98\Magento;

use Symfony\Component\Console\Application as BaseApplication;
use N98\Magento\Command\LocalConfig\GenerateCommand as GenerateLocalXmlConfigCommand;
use N98\Magento\Command\Database\DumpCommand as DumpDatabaseCommand;
use N98\Magento\Command\Database\InfoCommand as DatabaseInfoCommand;
use N98\Magento\Command\Config\DumpCommand as ConfigPrintCommand;
use N98\Magento\Command\Cache\ClearCommand as CacheClearCommand;
use N98\Magento\Command\Cache\ListCommand as CacheListCommand;
use N98\Magento\Command\Cache\EnableCommand as CacheEnableCommand;
use N98\Magento\Command\Cache\DisableCommand as CacheDisableCommand;
use N98\Magento\Command\Admin\User\ChangePasswordCommand as ChangeAdminUserPasswordCommand;
use N98\Magento\Command\Admin\User\ListCommand as AdminUserListCommand;
use N98\Magento\Command\Installer\InstallCommand;
use N98\Magento\Command\System\MaintenanceCommand as SystemMaintenanceCommand;
use N98\Magento\Command\System\InfoCommand as SystemInfoCommand;
use N98\Magento\Command\System\ModulesCommand as SystemModulesCommand;
use N98\Magento\Command\Developer\TemplateHintsCommand;
use N98\Magento\Command\Developer\TemplateHintsBlocksCommand;
use N98\Magento\Command\SelfUpdateCommand as SelfUpdateCommand;

class Application extends BaseApplication
{
    /**
     * @var string
     */
    const APP_NAME = 'n98-magerun';

    /**
     * @var string
     */
    const APP_VERSION = '1.4.2';

    public function __construct()
    {
        parent::__construct(self::APP_NAME, self::APP_VERSION);
        $this->add(new GenerateLocalXmlConfigCommand());
        $this->add(new DumpDatabaseCommand());
        $this->add(new DatabaseInfoCommand());
        $this->add(new ConfigPrintCommand());
        $this->add(new CacheClearCommand());
        $this->add(new CacheListCommand());
        $this->add(new CacheEnableCommand());
        $this->add(new CacheDisableCommand());
        $this->add(new ChangeAdminUserPasswordCommand());
        $this->add(new AdminUserListCommand());
        $this->add(new InstallCommand());
        $this->add(new SystemMaintenanceCommand());
        $this->add(new SystemInfoCommand());
        $this->add(new SystemModulesCommand());
        $this->add(new TemplateHintsCommand());
        $this->add(new TemplateHintsBlocksCommand());
        $this->add(new SelfUpdateCommand());
    }

}