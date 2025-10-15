<?php

declare(strict_types=1);

namespace N98\Magento\Application;

use ErrorException;
use N98\Util\ArrayFunctions;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Config consists of several parts which are merged.
 * The configuration which is global (not Magento project specific) is loaded
 * during construction.
 *
 * As soon as the Magento folder is known, loadStageTwo should be called.
 *
 * The toArray method only works if the Magento folder specific configuration is already loaded.
 *
 * Class ConfigurationLoader
 *
 * @package N98\Magento\Command
 */
class ConfigurationLoader
{
    /**
     * Config passed in the constructor
     * @var mixed[]
     */
    protected array $_initialConfig;

    protected ?array $_configArray = null;

    /**
     * Cache
     */
    protected ?array $_distConfig = null;

    /**
     * Cache
     */
    protected ?array $_pluginConfig = null;

    /**
     * Cache
     */
    protected ?array $_systemConfig = null;

    /**
     * Cache
     */
    protected ?array $_userConfig = null;

    /**
     * Cache
     */
    protected ?array $_projectConfig = null;

    protected string $_customConfigFilename = 'n98-magerun.yaml';

    protected bool $_isPharMode = true;

    protected OutputInterface $_output;

    /**
     * Load config
     * If $magentoRootFolder is null, only non-project config is loaded
     */
    public function __construct(array $config, bool $isPharMode, OutputInterface $output)
    {
        $this->_initialConfig = $config;
        $this->_isPharMode = $isPharMode;
        $this->_output = $output;
    }

    public function getPartialConfig(bool $loadExternalConfig = true): array
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        if ($loadExternalConfig) {
            $config = $this->loadSystemConfig($config);
            $config = $this->loadUserConfig($config);
        }

        return $config;
    }

    public function loadStageTwo(string $magentoRootFolder, bool $loadExternalConfig = true, string $magerunStopFileFolder = ''): void
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        if ($loadExternalConfig) {
            $config = $this->loadPluginConfig($config, $magentoRootFolder);
            $config = $this->loadSystemConfig($config);
            $config = $this->loadUserConfig($config, $magentoRootFolder);
            $config = $this->loadProjectConfig($magentoRootFolder, $magerunStopFileFolder, $config);
        }

        $this->_configArray = $config;
    }

    /**
     * @throws ErrorException
     */
    public function toArray(): array
    {
        if (is_null($this->_configArray)) {
            throw new ErrorException('Configuration not yet fully loaded');
        }

        return $this->_configArray;
    }

    protected function loadDistConfig(array $initConfig): array
    {
        if (is_null($this->_distConfig)) {
            $distConfigFilePath = __DIR__ . '/../../../../config.yaml';
            $this->logDebug('Load dist config <comment>' . $distConfigFilePath . '</comment>');
            $this->_distConfig = ConfigFile::createFromFile($distConfigFilePath)->toArray();
        } else {
            $this->logDebug('Load dist config <comment>cached</comment>');
        }

        return ArrayFunctions::mergeArrays($this->_distConfig, $initConfig);
    }

    /**
     * Check if there is a global config file in /etc folder
     */
    public function loadSystemConfig(array $config): array
    {
        if (is_null($this->_systemConfig)) {
            if (OperatingSystem::isWindows()) {
                $systemWideConfigFile = getenv('WINDIR') . '/' . $this->_customConfigFilename;
            } else {
                $systemWideConfigFile = '/etc/' . $this->_customConfigFilename;
            }

            if ($systemWideConfigFile && file_exists($systemWideConfigFile)) {
                $this->logDebug('Load system config <comment>' . $systemWideConfigFile . '</comment>');
                $this->_systemConfig = Yaml::parse($systemWideConfigFile);
            } else {
                $this->_systemConfig = [];
            }
        }

        return ArrayFunctions::mergeArrays($config, $this->_systemConfig);
    }

    /**
     * Load config from all installed bundles
     * @param array<string, mixed> $config
     */
    public function loadPluginConfig(array $config, string $magentoRootFolder): array
    {
        if (is_null($this->_pluginConfig)) {
            $this->_pluginConfig = [];
            $customName = pathinfo($this->_customConfigFilename, PATHINFO_FILENAME);
            if (OperatingSystem::isWindows()) {
                $config['plugin']['folders'][] = getenv('WINDIR') . '/' . $customName . '/modules';
                $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/' . $customName . '/modules';
            }

            $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/.' . $customName . '/modules';
            $config['plugin']['folders'][] = $magentoRootFolder . '/lib/' . $customName . '/modules';

            # Modules placed in vendor folder
            $vendorDir = $this->getVendorDir();
            if (strlen($vendorDir) !== 0) {
                $this->logDebug('Vendor directory <comment>' . $vendorDir . '</comment>');
                $this->traversePluginFoldersForConfigFile($magentoRootFolder, $vendorDir, 2);
            }

            # Glob plugin folders
            $this->traversePluginFoldersForConfigFile($magentoRootFolder, $config['plugin']['folders'], 1);
        }

        return ArrayFunctions::mergeArrays($config, $this->_pluginConfig);
    }

    /**
     * @param string|array $in
     */
    private function traversePluginFoldersForConfigFile(string $magentoRootFolder, $in, int $depth): void
    {
        $basename = $this->_customConfigFilename;
        $in = array_filter((array) $in, function ($value): bool {
            return (string) $value !== '';
        });
        if (1 > count($in = array_filter($in, 'is_dir'))) {
            return;
        }

        $finder = Finder::create()
            ->files()
            ->depth($depth)
            ->followLinks()
            ->ignoreUnreadableDirs(true)
            ->name($basename)
            ->in($in);

        foreach ($finder as $file) {
            $this->registerPluginConfigFile($magentoRootFolder, $file);
        }
    }

    /**
     * Check if there is a user config file. ~/.n98-magerun.yaml
     */
    public function loadUserConfig(array $config, string $magentoRootFolder = ''): array
    {
        if (is_null($this->_userConfig)) {
            $this->_userConfig = [];
            $configLocator = new ConfigLocator($this->_customConfigFilename, $magentoRootFolder);
            if (($userConfigFile = $configLocator->getUserConfigFile()) instanceof ConfigFile) {
                $this->logDebug('Load user config <comment>' . $userConfigFile->getPath() . '</comment>');
                $this->_userConfig = $userConfigFile->toArray();
            }
        }

        return ArrayFunctions::mergeArrays($config, $this->_userConfig);
    }

    /**
     * See MAGENTO_ROOT/app/etc/n98-magerun.yaml
     */
    public function loadProjectConfig(string $magentoRootFolder, string $magerunStopFileFolder, array $config): array
    {
        if (!is_null($this->_projectConfig)) {
            return ArrayFunctions::mergeArrays($config, $this->_projectConfig);
        }

        $this->_projectConfig = [];

        $configLocator = new ConfigLocator($this->_customConfigFilename, $magentoRootFolder);

        if (($projectConfigFile = $configLocator->getProjectConfigFile()) instanceof ConfigFile) {
            $this->_projectConfig = $projectConfigFile->toArray();
        }

        if (($stopFileConfigFile = $configLocator->getStopFileConfigFile($magerunStopFileFolder)) instanceof ConfigFile) {
            $this->_projectConfig = $stopFileConfigFile->mergeArray($this->_projectConfig);
        }

        return ArrayFunctions::mergeArrays($config, $this->_projectConfig);
    }

    /**
     * Loads a plugin config file and merges it to plugin config
     */
    protected function registerPluginConfigFile(string $magentoRootFolder, SplFileInfo $file): void
    {
        $path = $file->getPathname();

        $this->logDebug('Load plugin config <comment>' . $path . '</comment>');
        $localPluginConfigFile = ConfigFile::createFromFile($path);
        $localPluginConfigFile->applyVariables($magentoRootFolder, $file);

        $this->_pluginConfig = $localPluginConfigFile->mergeArray($this->_pluginConfig);
    }

    public function getVendorDir(): string
    {
        $configurationLoaderDir = $this->getConfigurationLoaderDir();

        /* source version vendor folder (also in phar archive) */
        $vendorFolder = $configurationLoaderDir . '/../../../../vendor';
        if (is_dir($vendorFolder)) {
            return $vendorFolder;
        }

        /* composer installed vendor folder */
        $vendorFolder = $configurationLoaderDir . '/../../../../../../../vendor';
        if (is_dir($vendorFolder)) {
            return $vendorFolder;
        }

        return '';
    }

    public function getConfigurationLoaderDir(): string
    {
        return __DIR__;
    }

    private function logDebug(string $message): void
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->_output->getVerbosity()) {
            $this->log('<debug>' . $message . '</debug>');
        }
    }

    private function log(string $message): void
    {
        $this->_output->writeln($message);
    }
}
