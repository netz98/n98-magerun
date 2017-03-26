<?php

namespace N98\Magento\Application;

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
     *
     * @var array
     */
    protected $_initialConfig;

    /**
     * @var array
     */
    protected $_configArray = null;

    /**
     * Cache
     *
     * @var array
     */
    protected $_distConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_pluginConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_systemConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_userConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_projectConfig;

    /**
     * @var string
     */
    protected $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * @var bool
     */
    protected $_isPharMode = true;

    /**
     * @var OutputInterface
     */
    protected $_output;

    /**
     * Load config
     * If $magentoRootFolder is null, only non-project config is loaded
     *
     * @param array $config
     * @param bool $isPharMode
     * @param OutputInterface $output
     */
    public function __construct(array $config, $isPharMode, OutputInterface $output)
    {
        $this->_initialConfig = $config;
        $this->_isPharMode = $isPharMode;
        $this->_output = $output;
    }

    /**
     * @param bool $loadExternalConfig
     * @return array
     */
    public function getPartialConfig($loadExternalConfig = true)
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        if ($loadExternalConfig) {
            $config = $this->loadSystemConfig($config);
            $config = $this->loadUserConfig($config);
        }

        return $config;
    }

    /**
     * @param string $magentoRootFolder
     * @param bool $loadExternalConfig
     * @param string $magerunStopFileFolder
     */
    public function loadStageTwo($magentoRootFolder, $loadExternalConfig = true, $magerunStopFileFolder = '')
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
     * @throws \ErrorException
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->_configArray == null) {
            throw new \ErrorException('Configuration not yet fully loaded');
        }

        return $this->_configArray;
    }

    /**
     * @param array $initConfig
     *
     * @return array
     */
    protected function loadDistConfig(array $initConfig)
    {
        if ($this->_distConfig == null) {
            $distConfigFilePath = __DIR__ . '/../../../../config.yaml';
            $this->logDebug('Load dist config <comment>' . $distConfigFilePath . '</comment>');
            $this->_distConfig = ConfigFile::createFromFile($distConfigFilePath)->toArray();
        } else {
            $this->logDebug('Load dist config <comment>cached</comment>');
        }

        $config = ArrayFunctions::mergeArrays($this->_distConfig, $initConfig);

        return $config;
    }

    /**
     * Check if there is a global config file in /etc folder
     *
     * @param array $config
     *
     * @return array
     */
    public function loadSystemConfig(array $config)
    {
        if ($this->_systemConfig == null) {
            if (OperatingSystem::isWindows()) {
                $systemWideConfigFile = getenv('WINDIR') . '/' . $this->_customConfigFilename;
            } else {
                $systemWideConfigFile = '/etc/' . $this->_customConfigFilename;
            }

            if ($systemWideConfigFile && file_exists($systemWideConfigFile)) {
                $this->logDebug('Load system config <comment>' . $systemWideConfigFile . '</comment>');
                $this->_systemConfig = Yaml::parse($systemWideConfigFile);
            } else {
                $this->_systemConfig = array();
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_systemConfig);

        return $config;
    }

    /**
     * Load config from all installed bundles
     *
     * @param array $config
     * @param string $magentoRootFolder
     *
     * @return array
     */
    public function loadPluginConfig(array $config, $magentoRootFolder)
    {
        if (null === $this->_pluginConfig) {
            $this->_pluginConfig = array();
            $customName = pathinfo($this->_customConfigFilename, PATHINFO_FILENAME);
            if (OperatingSystem::isWindows()) {
                $config['plugin']['folders'][] = getenv('WINDIR') . '/' . $customName . '/modules';
                $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/' . $customName . '/modules';
            }
            $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/.' . $customName . '/modules';
            $config['plugin']['folders'][] = $magentoRootFolder . '/lib/' . $customName . '/modules';

            # Modules placed in vendor folder
            $vendorDir = $this->getVendorDir();
            if (strlen($vendorDir)) {
                $this->logDebug('Vendor directory <comment>' . $vendorDir . '</comment>');
                $this->traversePluginFoldersForConfigFile($magentoRootFolder, $vendorDir, 2);
            }

            # Glob plugin folders
            $this->traversePluginFoldersForConfigFile($magentoRootFolder, $config['plugin']['folders'], 1);
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_pluginConfig);

        return $config;
    }

    /**
     * @param string $magentoRootFolder
     * @param string|array $in
     * @param integer $depth
     */
    private function traversePluginFoldersForConfigFile($magentoRootFolder, $in, $depth)
    {
        $basename = $this->_customConfigFilename;
        if (1 > count($in = array_filter(array_filter((array) $in, 'strlen'), 'is_dir'))) {
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
     *
     * @param array $config
     * @param string $magentoRootFolder [optional]
     *
     * @return array
     */
    public function loadUserConfig(array $config, $magentoRootFolder = null)
    {
        if (null === $this->_userConfig) {
            $this->_userConfig = array();
            $locator = new ConfigLocator($this->_customConfigFilename, $magentoRootFolder);
            if ($userConfigFile = $locator->getUserConfigFile()) {
                $this->_userConfig = $userConfigFile->toArray();
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_userConfig);

        return $config;
    }

    /**
     * MAGENTO_ROOT/app/etc/n98-magerun.yaml
     *
     * @param string $magentoRootFolder
     * @param string $magerunStopFileFolder
     * @param array $config
     *
     * @return array
     */
    public function loadProjectConfig($magentoRootFolder, $magerunStopFileFolder, array $config)
    {
        if (null !== $this->_projectConfig) {
            return ArrayFunctions::mergeArrays($config, $this->_projectConfig);
        }

        $this->_projectConfig = array();

        $locator = new ConfigLocator($this->_customConfigFilename, $magentoRootFolder);

        if ($projectConfigFile = $locator->getProjectConfigFile()) {
            $this->_projectConfig = $projectConfigFile->toArray();
        }

        if ($stopFileConfigFile = $locator->getStopFileConfigFile($magerunStopFileFolder)) {
            $this->_projectConfig = $stopFileConfigFile->mergeArray($this->_projectConfig);
        }

        return ArrayFunctions::mergeArrays($config, $this->_projectConfig);
    }

    /**
     * Loads a plugin config file and merges it to plugin config
     *
     * @param string $magentoRootFolder
     * @param SplFileInfo $file
     */
    protected function registerPluginConfigFile($magentoRootFolder, $file)
    {
        $path = $file->getPathname();

        $this->logDebug('Load plugin config <comment>' . $path . '</comment>');
        $localPluginConfigFile = ConfigFile::createFromFile($path);
        $localPluginConfigFile->applyVariables($magentoRootFolder, $file);
        $this->_pluginConfig = $localPluginConfigFile->mergeArray($this->_pluginConfig);
    }

    /**
     * @return string
     */
    public function getVendorDir()
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

    /**
     * @return string
     */
    public function getConfigurationLoaderDir()
    {
        return __DIR__;
    }

    /**
     * @param string $message
     */
    private function logDebug($message)
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->_output->getVerbosity()) {
            $this->log('<debug>' . $message . '</debug>');
        }
    }

    /**
     * @param string $message
     */
    private function log($message)
    {
        $this->_output->writeln($message);
    }
}
