<?php

namespace N98\Magento;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use N98\Magento\Command\ConfigurationLoader;
use N98\Magento\Command\LocalConfig\GenerateCommand as GenerateLocalXmlConfigCommand;
use N98\Magento\Command\Database\DumpCommand as DatabaseDumpCommand;
use N98\Magento\Command\Database\DropCommand as DatabaseDropCommand;
use N98\Magento\Command\Database\InfoCommand as DatabaseInfoCommand;
use N98\Magento\Command\Database\ConsoleCommand as DatabaseConsoleCommand;
use N98\Magento\Command\Config\DumpCommand as ConfigPrintCommand;
use N98\Magento\Command\Config\GetCommand as ConfigGetCommand;
use N98\Magento\Command\Config\SetCommand as ConfigSetCommand;
use N98\Magento\Command\Design\DemoNoticeCommand as DesignDemoNoticeCommand;
use N98\Magento\Command\Cache\CleanCommand as CacheCleanCommand;
use N98\Magento\Command\Cache\FlushCommand as CacheFlushCommand;
use N98\Magento\Command\Cache\ListCommand as CacheListCommand;
use N98\Magento\Command\Cache\EnableCommand as CacheEnableCommand;
use N98\Magento\Command\Cache\DisableCommand as CacheDisableCommand;
use N98\Magento\Command\Indexer\ListCommand as IndexerListCommand;
use N98\Magento\Command\Indexer\ReindexCommand as IndexerReindexCommand;
use N98\Magento\Command\Indexer\ReindexAllCommand as IndexerReindexAllCommand;
use N98\Magento\Command\Admin\User\ChangePasswordCommand as ChangeAdminUserPasswordCommand;
use N98\Magento\Command\Admin\User\ListCommand as AdminUserListCommand;
use N98\Magento\Command\Admin\User\CreateUserCommand as AdminUserCreateCommand;
use N98\Magento\Command\Admin\DisableNotificationsCommand;
use N98\Magento\Command\Installer\InstallCommand;
use N98\Magento\Command\Installer\UninstallCommand;
use N98\Magento\Command\System\MaintenanceCommand as SystemMaintenanceCommand;
use N98\Magento\Command\System\InfoCommand as SystemInfoCommand;
use N98\Magento\Command\System\CheckCommand as SystemCheckCommand;
use N98\Magento\Command\System\Setup\RunCommand as SetupRunScriptsCommand;
use N98\Magento\Command\System\Setup\CompareVersionsCommand as SetupCompareVersionsCommand;
use N98\Magento\Command\System\Store\ListCommand as SystemStoreListCommand;
use N98\Magento\Command\System\Store\Config\BaseUrlListCommand as SystemStoreConfigBaseUrlListCommand;
use N98\Magento\Command\System\Website\ListCommand as SystemWebsiteListCommand;
use N98\Magento\Command\System\Cron\ListCommand as SystemCronListCommand;
use N98\Magento\Command\System\Url\ListCommand as SystemUrlListCommand;
use N98\Magento\Command\Developer\TemplateHintsCommand;
use N98\Magento\Command\Developer\TemplateHintsBlocksCommand;
use N98\Magento\Command\Developer\TranslateInlineShopCommand;
use N98\Magento\Command\Developer\TranslateInlineAdminCommand;
use N98\Magento\Command\Developer\ProfilerCommand;
use N98\Magento\Command\Developer\SymlinksCommand;
use N98\Magento\Command\Developer\LogCommand as DevelopmentLogCommand;
use N98\Magento\Command\Developer\Module\ListCommand as ModuleListCommand;
use N98\Magento\Command\Developer\Module\Rewrite\ListCommand as ModuleRewriteListCommand;
use N98\Magento\Command\Developer\Module\Rewrite\ConflictsCommand as ModuleRewriteConflictsCommand;
use N98\Magento\Command\Developer\Module\CreateCommand as ModuleCreateCommand;
use N98\Magento\Command\Developer\Module\Observer\ListCommand as ModuleObserverListCommand;
use N98\Magento\Command\MagentoConnect\ListExtensionsCommand as MagentoConnectionListExtensionsCommand;
use N98\Magento\Command\MagentoConnect\InstallExtensionCommand as MagentoConnectionInstallExtensionCommand;
use N98\Magento\Command\MagentoConnect\DownloadExtensionCommand as MagentoConnectionDownloadExtensionCommand;
use N98\Magento\Command\MagentoConnect\UpgradeExtensionCommand as MagentoConnectionUpgradeExtensionCommand;
use N98\Magento\Command\Cms\Page\PublishCommand as MagentoCmsPagePublishCommand;
use N98\Magento\Command\Cms\Banner\ToggleCommand as MagentoCmsBannerToggleCommand;
use N98\Magento\Command\SelfUpdateCommand as SelfUpdateCommand;
use N98\Util\OperatingSystem;
use Xanido\Console\Helper\TableHelper;

class Application extends BaseApplication
{
    /**
     * @var int
     */
    const MAGENTO_MAJOR_VERSION_1 = 1;

    /**
     * @var int
     */
    const MAGENTO_MAJOR_VERSION_2 = 2;

    /**
     * @var string
     */
    const APP_NAME = 'n98-magerun';

    /**
     * @var string
     */
    const APP_VERSION = '1.40.0';

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $autoloader;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $_magentoRootFolder = null;

    /**
     * @var bool
     */
    protected $_magentoEnterprise = false;

    /**
     * @var int
     */
    protected $_magentoMajorVersion = self::MAGENTO_MAJOR_VERSION_1;

    public function __construct($autoloader)
    {
        $this->autoloader = $autoloader;
        parent::__construct(self::APP_NAME, self::APP_VERSION);

        // Suppress DateTime warnings
        date_default_timezone_set(@date_default_timezone_get());

        $this->detectMagento();

        $configLoader = new ConfigurationLoader($this->_magentoRootFolder);
        $this->config = $configLoader->toArray();

        $this->registerHelpers();
        $this->registerCustomAutoloaders();
        $this->registerCustomCommands();

        $this->add(new GenerateLocalXmlConfigCommand());
        $this->add(new DatabaseDumpCommand());
        $this->add(new DatabaseDropCommand());
        $this->add(new DatabaseInfoCommand());
        $this->add(new DatabaseConsoleCommand());
        $this->add(new ConfigPrintCommand());
        $this->add(new ConfigGetCommand());
        $this->add(new ConfigSetCommand());
        $this->add(new CacheCleanCommand());
        $this->add(new CacheFlushCommand());
        $this->add(new CacheListCommand());
        $this->add(new CacheEnableCommand());
        $this->add(new CacheDisableCommand());
        $this->add(new IndexerListCommand());
        $this->add(new IndexerReindexCommand());
        $this->add(new IndexerReindexAllCommand());
        $this->add(new ChangeAdminUserPasswordCommand());
        $this->add(new AdminUserListCommand());
        $this->add(new AdminUserCreateCommand());
        $this->add(new DisableNotificationsCommand());
        $this->add(new DesignDemoNoticeCommand());
        $this->add(new InstallCommand());
        $this->add(new UninstallCommand());
        $this->add(new SystemMaintenanceCommand());
        $this->add(new SystemInfoCommand());
        $this->add(new SystemCheckCommand());
        $this->add(new SystemStoreListCommand());
        $this->add(new SystemStoreConfigBaseUrlListCommand());
        $this->add(new SystemWebsiteListCommand());
        $this->add(new SystemCronListCommand());
        $this->add(new SystemUrlListCommand());
        $this->add(new SetupRunScriptsCommand());
        $this->add(new SetupCompareVersionsCommand());
        $this->add(new TemplateHintsCommand());
        $this->add(new TemplateHintsBlocksCommand());
        $this->add(new TranslateInlineShopCommand());
        $this->add(new TranslateInlineAdminCommand());
        $this->add(new ProfilerCommand());
        $this->add(new SymlinksCommand());
        $this->add(new DevelopmentLogCommand());
        $this->add(new ModuleListCommand());
        $this->add(new ModuleRewriteListCommand());
        $this->add(new ModuleRewriteConflictsCommand());
        $this->add(new ModuleCreateCommand());
        $this->add(new ModuleObserverListCommand());

        if (!OperatingSystem::isWindows()) {
            $this->add(new MagentoConnectionListExtensionsCommand());
            $this->add(new MagentoConnectionInstallExtensionCommand());
            $this->add(new MagentoConnectionDownloadExtensionCommand());
            $this->add(new MagentoConnectionUpgradeExtensionCommand());
        }

        $this->add(new MagentoCmsPagePublishCommand());
        $this->add(new MagentoCmsBannerToggleCommand());
        $this->add(new SelfUpdateCommand());
    }

    /**
     * Search for magento root folder
     *
     * @param OutputInterface $output
     * @param bool $silent print debug messages
     */
    public function detectMagento()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $folder = exec('@echo %cd%'); // @TODO not currently tested!!!
        } else {
            $folder = exec('pwd');
        }

        $folders = array();
        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        foreach ($folderParts as $key => $part) {
            $explodedFolder = implode(DIRECTORY_SEPARATOR, array_slice($folderParts, 0, $key + 1));
            if ($explodedFolder !== '') {
                $folders[] = $explodedFolder;
            }
        }

        foreach (array_reverse($folders) as $searchFolder) {
            $finder = new Finder();
            $finder
                ->directories()
                ->depth(0)
                ->followLinks()
                ->name('app')
                ->name('skin')
                ->name('lib')
                ->in($searchFolder);

            if ($finder->count() >= 2) {
                $files = iterator_to_array($finder, false); /* @var $file \SplFileInfo */

                if (count($files) == 2) {
                    // Magento 2 has no skin folder.
                    // @TODO find a better magento 2.x check
                    $this->_magentoMajorVersion = self::MAGENTO_MAJOR_VERSION_2;
                }

                $this->_magentoRootFolder = dirname($files[0]->getRealPath());

                if (is_callable(array('\Mage', 'getEdition'))) {
                    $this->_magentoEnterprise = (\Mage::getEdition() == 'Enterprise');
                } else {
                    $this->_magentoEnterprise = is_dir($this->_magentoRootFolder . '/app/code/core/Enterprise');
                }

                return;
            }
        }
    }

    /**
     * @return boolean
     */
    public function isMagentoEnterprise()
    {
        return $this->_magentoEnterprise;
    }

    /**
     * @return string
     */
    public function getMagentoRootFolder()
    {
        return $this->_magentoRootFolder;
    }

    /**
     * @return int
     */
    public function getMagentoMajorVersion()
    {
        return $this->_magentoMajorVersion;
    }


    /**
     * Add own helpers to helperset.
     */
    protected function registerHelpers()
    {
        $helperSet = $this->getHelperSet();
        $helperSet->set(new TableHelper(), 'table');
    }

    /**
     * Adds autoloader prefixes from user's config
     */
    protected function registerCustomAutoloaders()
    {
        if (isset($this->config['autoloaders']) && is_array($this->config['autoloaders'])) {
            foreach ($this->config['autoloaders'] as $prefix => $path) {
                $this->autoloader->add($prefix, $path);
            }
        }
    }

    protected function registerCustomCommands()
    {
        if (isset($this->config['commands']['customCommands']) && is_array($this->config['commands']['customCommands'])) {
            foreach ($this->config['commands']['customCommands'] as $commandClass) {
                $this->add(new $commandClass);
            }
        }
    }

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public function setAutoloader($autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public function getAutoloader()
    {
        return $this->autoloader;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}