<?php

namespace N98\Magento;

use N98\Magento\Command\Admin\DisableNotificationsCommand;
use N98\Magento\Command\Admin\User\ChangePasswordCommand as ChangeAdminUserPasswordCommand;
use N98\Magento\Command\Admin\User\CreateUserCommand as AdminUserCreateCommand;
use N98\Magento\Command\Admin\User\ListCommand as AdminUserListCommand;
use N98\Magento\Command\Cache\CleanCommand as CacheCleanCommand;
use N98\Magento\Command\Cache\DisableCommand as CacheDisableCommand;
use N98\Magento\Command\Cache\EnableCommand as CacheEnableCommand;
use N98\Magento\Command\Cache\FlushCommand as CacheFlushCommand;
use N98\Magento\Command\Cache\ListCommand as CacheListCommand;
use N98\Magento\Command\Cms\Banner\ToggleCommand as MagentoCmsBannerToggleCommand;
use N98\Magento\Command\Cms\Page\PublishCommand as MagentoCmsPagePublishCommand;
use N98\Magento\Command\Config\DumpCommand as ConfigPrintCommand;
use N98\Magento\Command\Config\GetCommand as ConfigGetCommand;
use N98\Magento\Command\Config\SetCommand as ConfigSetCommand;
use N98\Magento\Command\ConfigurationLoader;
use N98\Magento\Command\Customer\ChangePasswordCommand as CustomerChangePasswordCommand;
use N98\Magento\Command\Customer\CreateCommand as CustomerCreateCommand;
use N98\Magento\Command\Customer\CreateDummyCommand as CustomerCreateDummyCommand;
use N98\Magento\Command\Customer\InfoCommand as CustomerInfoCommand;
use N98\Magento\Command\Customer\ListCommand as CustomerListCommand;
use N98\Magento\Command\Database\ConsoleCommand as DatabaseConsoleCommand;
use N98\Magento\Command\Database\DropCommand as DatabaseDropCommand;
use N98\Magento\Command\Database\DumpCommand as DatabaseDumpCommand;
use N98\Magento\Command\Database\ImportCommand as DatabaseImportCommand;
use N98\Magento\Command\Database\InfoCommand as DatabaseInfoCommand;
use N98\Magento\Command\Database\QueryCommand as DatabaseQueryCommand;
use N98\Magento\Command\Design\DemoNoticeCommand as DesignDemoNoticeCommand;
use N98\Magento\Command\Developer\Ide\PhpStorm\MetaCommand as DevelopmentIdePhpStormMetaCommand;
use N98\Magento\Command\Developer\ConsoleCommand as DevelopmentConsoleCommand;
use N98\Magento\Command\Developer\Log\DbCommand as DevelopmentLogDbCommand;
use N98\Magento\Command\Developer\Log\LogCommand as DevelopmentLogCommand;
use N98\Magento\Command\Developer\Log\SizeCommand as DevelopmentLogSizeCommand;
use N98\Magento\Command\Developer\Module\CreateCommand as ModuleCreateCommand;
use N98\Magento\Command\Developer\Module\ListCommand as ModuleListCommand;
use N98\Magento\Command\Developer\Module\Observer\ListCommand as ModuleObserverListCommand;
use N98\Magento\Command\Developer\Module\Rewrite\ConflictsCommand as ModuleRewriteConflictsCommand;
use N98\Magento\Command\Developer\Module\Rewrite\ListCommand as ModuleRewriteListCommand;
use N98\Magento\Command\Developer\ProfilerCommand;
use N98\Magento\Command\Developer\Report\CountCommand as DevelopmentReportCountCommand;
use N98\Magento\Command\Developer\SymlinksCommand;
use N98\Magento\Command\Developer\TemplateHintsBlocksCommand;
use N98\Magento\Command\Developer\TemplateHintsCommand;
use N98\Magento\Command\Developer\Theme\DuplicatesCommand as ThemeDuplicatesCommand;
use N98\Magento\Command\Developer\Theme\ListCommand as ThemeListCommand;
use N98\Magento\Command\Developer\TranslateInlineAdminCommand;
use N98\Magento\Command\Developer\TranslateInlineShopCommand;
use N98\Magento\Command\Indexer\ListCommand as IndexerListCommand;
use N98\Magento\Command\Indexer\ReindexAllCommand as IndexerReindexAllCommand;
use N98\Magento\Command\Indexer\ReindexCommand as IndexerReindexCommand;
use N98\Magento\Command\Installer\InstallCommand;
use N98\Magento\Command\Installer\UninstallCommand;
use N98\Magento\Command\LocalConfig\GenerateCommand as GenerateLocalXmlConfigCommand;
use N98\Magento\Command\MagentoConnect\DownloadExtensionCommand as MagentoConnectionDownloadExtensionCommand;
use N98\Magento\Command\MagentoConnect\InstallExtensionCommand as MagentoConnectionInstallExtensionCommand;
use N98\Magento\Command\MagentoConnect\ListExtensionsCommand as MagentoConnectionListExtensionsCommand;
use N98\Magento\Command\MagentoConnect\UpgradeExtensionCommand as MagentoConnectionUpgradeExtensionCommand;
use N98\Magento\Command\OpenBrowserCommand;
use N98\Magento\Command\ScriptCommand;
use N98\Magento\Command\SelfUpdateCommand as SelfUpdateCommand;
use N98\Magento\Command\ShellCommand;
use N98\Magento\Command\System\CheckCommand as SystemCheckCommand;
use N98\Magento\Command\System\Cron\HistoryCommand as SystemCronHistoryCommand;
use N98\Magento\Command\System\Cron\ListCommand as SystemCronListCommand;
use N98\Magento\Command\System\Cron\RunCommand as SystemCronRunCommand;
use N98\Magento\Command\System\InfoCommand as SystemInfoCommand;
use N98\Magento\Command\System\MaintenanceCommand as SystemMaintenanceCommand;
use N98\Magento\Command\System\Setup\CompareVersionsCommand as SetupCompareVersionsCommand;
use N98\Magento\Command\System\Setup\RunCommand as SetupRunScriptsCommand;
use N98\Magento\Command\System\Store\Config\BaseUrlListCommand as SystemStoreConfigBaseUrlListCommand;
use N98\Magento\Command\System\Store\ListCommand as SystemStoreListCommand;
use N98\Magento\Command\System\Url\ListCommand as SystemUrlListCommand;
use N98\Magento\Command\System\Website\ListCommand as SystemWebsiteListCommand;
use N98\Magento\EntryPoint\Magerun as MagerunEntryPoint;
use N98\Util\Console\Helper\ParameterHelper;
use N98\Util\Console\Helper\TableHelper;
use N98\Util\OperatingSystem;
use N98\Util\String;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

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
    const APP_VERSION = '1.65.1';

    /**
     * @var string
     */
    private static $logo = "
     ___ ___
 _ _/ _ ( _ )___ _ __  __ _ __ _ ___ _ _ _  _ _ _
| ' \_, / _ \___| '  \/ _` / _` / -_) '_| || | ' \
|_||_/_/\___/   |_|_|_\__,_\__, \___|_|  \_,_|_||_|
                           |___/
";
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $autoloader;
    /**
     * @var array
     */
    protected $config = array();
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

    /**
     * @var bool
     */
    protected $_isPharMode = false;

    /**
     * @var bool
     */
    protected $_isInitialized = false;

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     * @param bool                           $isPharMode
     */
    public function __construct($autoloader = null)
    {
        $this->autoloader = $autoloader;
        parent::__construct(self::APP_NAME, self::APP_VERSION);
    }

    /**
     * @return \Symfony\Component\Console\Input\InputDefinition|void
     */
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();
        $rootDirOption = new InputOption(
            '--root-dir',
            '',
            InputOption::VALUE_OPTIONAL,
            'Force magento root dir. No auto detection'
        );
        $inputDefinition->addOption($rootDirOption);

        return $inputDefinition;
    }


    /**
     * Search for magento root folder
     */
    public function detectMagento()
    {

        $specialGlobalOptions = getopt('', array('root-dir:'));

        if (count($specialGlobalOptions) > 0) {
            $folder = realpath($specialGlobalOptions['root-dir']);
            if (is_dir($folder)) {
                \chdir($folder);
            }
        }

        if (OperatingSystem::isWindows()) {
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
                $files = iterator_to_array($finder, false);
                /* @var $file \SplFileInfo */

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
     * Add own helpers to helperset.
     */
    protected function registerHelpers()
    {
        $helperSet = $this->getHelperSet();
        $helperSet->set(new TableHelper(), 'table');
        $helperSet->set(new ParameterHelper(), 'parameter');
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
        if (isset($this->config['commands']['customCommands'])
            && is_array($this->config['commands']['customCommands'])
        ) {
            foreach ($this->config['commands']['customCommands'] as $commandClass) {
                $this->add(new $commandClass);
            }
        }
    }

    /**
     * Override standard command registration. We want alias support.
     *
     * @param \Symfony\Component\Console\Command\Command $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function add(Command $command)
    {
        $this->registerConfigCommandAlias($command);

        return parent::add($command);
    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     */
    protected function registerConfigCommandAlias(Command $command)
    {
        if ($this->hasConfigCommandAliases()) {
            foreach ($this->config['commands']['aliases'] as $alias) {
                if (!is_array($alias)) {
                    continue;
                }

                $aliasCommandName = key($alias);
                $commandString = $alias[$aliasCommandName];

                list($originalCommand) = explode(' ', $commandString);
                if ($command->getName() == $originalCommand) {
                    $currentCommandAliases = $command->getAliases();
                    $currentCommandAliases[] = $aliasCommandName;
                    $command->setAliases($currentCommandAliases);
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function hasConfigCommandAliases()
    {
        return isset($this->config['commands']['aliases']) && is_array($this->config['commands']['aliases']);
    }

    /**
     * @param bool $mode
     */
    public function setPharMode($mode)
    {
        $this->_isPharMode = $mode;
    }

    /**
     * @return bool
     */
    public function isPharMode()
    {
        return $this->_isPharMode;
    }

    public function initMagento()
    {
        if ($this->getMagentoRootFolder() !== null) {
            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
                require_once $this->getMagentoRootFolder() . '/app/bootstrap.php';
                if (version_compare(\Mage::getVersion(), '2.0.0.0-dev42') >= 0) {
                    $params = array(
                        \Mage::PARAM_RUN_CODE => 'admin',
                        \Mage::PARAM_RUN_TYPE => 'store',
                        'entryPoint' => basename(__FILE__),
                    );
                    new MagerunEntryPoint(BP, $params);
                } else
                    if (version_compare(\Mage::getVersion(), '2.0.0.0-dev41') >= 0) {
                        \Mage::app(array('MAGE_RUN_CODE' => 'admin'));
                    } else {
                        \Mage::app('admin');
                    }
            } else {
                require_once $this->getMagentoRootFolder() . '/app/Mage.php';
                \Mage::app('admin');
            }
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    public function getLongVersion()
    {
        return parent::getLongVersion() . ' by <info>netz98 new media GmbH</info>';
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
     * @return \Composer\Autoload\ClassLoader
     */
    public function getAutoloader()
    {
        return $this->autoloader;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public function setAutoloader($autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Runs the current application with possible command aliases
     *
     * @param InputInterface $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $input = $this->checkConfigCommandAlias($input);

        parent::doRun($input, $output);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return \Symfony\Component\Console\Input\ArgvInput|\Symfony\Component\Console\Input\InputInterface
     */
    protected function checkConfigCommandAlias(InputInterface $input)
    {
        if ($this->hasConfigCommandAliases()) {
            foreach ($this->config['commands']['aliases'] as $alias) {
                if (is_array($alias)) {
                    $aliasCommandName = key($alias);
                    if ($input->getFirstArgument() == $aliasCommandName) {
                        $aliasCommandParams = array_slice(String::trimExplodeEmpty(' ', $alias[$aliasCommandName]), 1);
                        if (count($aliasCommandParams) > 0) {
                            // replace with aliased data
                            $mergedParams = array_merge(
                                array_slice($_SERVER['argv'], 0, 2),
                                $aliasCommandParams,
                                array_slice($_SERVER['argv'], 2)
                            );
                            $input = new ArgvInput($mergedParams);
                        }
                    }
                }
            }
            return $input;
        }
        return $input;
    }

    /**
     * Returns an array of possible abbreviations given a set of names.
     * This is the reverted version if changed method of symfony framework.
     * I reverted this to enable commands like customer:create and customer:create:dummy.
     * This will not work with current dev-master of symfony console components which
     * causes an error like "Command "customer:create" is ambiguous".
     *
     * @TODO Check if this is a bug in symfony or wanted.
     * @param array $names An array of names
     *
     * @return array An array of abbreviations
     */
    public static function getAbbreviations($names)
    {
        $abbrevs = array();
        foreach ($names as $name) {
            for ($len = strlen($name) - 1; $len > 0; --$len) {
                $abbrev = substr($name, 0, $len);
                if (!isset($abbrevs[$abbrev])) {
                    $abbrevs[$abbrev] = array($name);
                } else {
                    $abbrevs[$abbrev][] = $name;
                }
            }
        }

        // Non-abbreviations always get entered, even if they aren't unique
        foreach ($names as $name) {
            $abbrevs[$name] = array($name);
        }

        return $abbrevs;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->init();

        $return = parent::run($input, $output);

        // Fix for no return values -> used in interactive shell to prevent error output
        if ($return === null) {
            return 0;
        }

        return $return;
    }

    protected function registerCommands()
    {
        $this->add(new GenerateLocalXmlConfigCommand());
        $this->add(new DatabaseDumpCommand());
        $this->add(new DatabaseDropCommand());
        $this->add(new DatabaseInfoCommand());
        $this->add(new DatabaseImportCommand());
        $this->add(new DatabaseConsoleCommand());
        $this->add(new DatabaseQueryCommand());
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
        $this->add(new CustomerCreateCommand());
        $this->add(new CustomerListCommand());
        $this->add(new CustomerChangePasswordCommand());
        $this->add(new CustomerCreateDummyCommand());
        $this->add(new CustomerInfoCommand());
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
        $this->add(new SystemCronRunCommand());
        $this->add(new SystemCronHistoryCommand());
        $this->add(new SystemUrlListCommand());
        $this->add(new SetupRunScriptsCommand());
        $this->add(new SetupCompareVersionsCommand());
        $this->add(new TemplateHintsCommand());
        $this->add(new TemplateHintsBlocksCommand());
        $this->add(new TranslateInlineShopCommand());
        $this->add(new TranslateInlineAdminCommand());
        $this->add(new ThemeDuplicatesCommand());
        $this->add(new ThemeListCommand());
        $this->add(new ProfilerCommand());
        $this->add(new SymlinksCommand());
        $this->add(new DevelopmentLogCommand());
        $this->add(new DevelopmentLogDbCommand());
        $this->add(new DevelopmentLogSizeCommand());
        $this->add(new DevelopmentReportCountCommand());
        $this->add(new DevelopmentIdePhpStormMetaCommand());
        $this->add(new ModuleListCommand());
        $this->add(new ModuleRewriteListCommand());
        $this->add(new ModuleRewriteConflictsCommand());
        $this->add(new ModuleCreateCommand());
        $this->add(new ModuleObserverListCommand());
        $this->add(new ShellCommand());
        $this->add(new ScriptCommand());
        $this->add(new MagentoConnectionListExtensionsCommand());
        $this->add(new MagentoConnectionInstallExtensionCommand());
        $this->add(new MagentoConnectionDownloadExtensionCommand());
        $this->add(new MagentoConnectionUpgradeExtensionCommand());
        $this->add(new OpenBrowserCommand());
        $this->add(new MagentoCmsPagePublishCommand());
        $this->add(new MagentoCmsBannerToggleCommand());
        $this->add(new DevelopmentConsoleCommand());
        $this->add(new SelfUpdateCommand());
        $this->add(new \N98\Magento\Command\Developer\AttributeProductCommand());
    }

    /**
     * @param array $initConfig
     * @return array
     */
    protected function _loadConfig($initConfig)
    {
        $configLoader = new ConfigurationLoader($initConfig, $this->_magentoRootFolder);

        return $configLoader->toArray();
    }

    public function init()
    {
        if (!$this->_isInitialized) {
            // Suppress DateTime warnings
            date_default_timezone_set(@date_default_timezone_get());

            $this->detectMagento();
            $this->config = $this->_loadConfig($this->config);
            $this->registerHelpers();
            if ($this->autoloader) {
                $this->registerCustomAutoloaders();
                $this->registerCustomCommands();
            }
            $this->registerCommands();

            $this->_isInitialized = true;
        }
    }

}