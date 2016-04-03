<?php

namespace N98\Magento\Application;

use N98\Util\ArrayFunctions;
use N98\Util\BinaryString;
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
            $this->_distConfig = ConfigFile::createFromFile($distConfigFilePath)->toArray();
        }
        $this->logDebug('Load dist config');

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
                $systemWideConfigFile = getenv('WINDIR') . DIRECTORY_SEPARATOR . $this->_customConfigFilename;
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
        if ($this->_pluginConfig == null) {
            $this->_pluginConfig = array();
            $moduleBaseFolders = array();
            $customFilename = $this->_customConfigFilename;
            $customName = pathinfo($customFilename, PATHINFO_FILENAME);
            if (OperatingSystem::isWindows()) {
                $config['plugin']['folders'][] = getenv('WINDIR') . '/' . $customName . '/modules';
                $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/' . $customName . '/modules';
            } else {
                $config['plugin']['folders'][] = OperatingSystem::getHomeDir() . '/.' . $customName . '/modules';
            }
            $config['plugin']['folders'][] = $magentoRootFolder . '/lib/' . $customName . '/modules';
            foreach ($config['plugin']['folders'] as $folder) {
                if (is_dir($folder)) {
                    $moduleBaseFolders[] = $folder;
                }
            }

            /**
             * Allow modules to be placed vendor folder if not in phar mode
             */
            if (!$this->_isPharMode) {
                if (is_dir($this->getVendorDir())) {
                    $finder = Finder::create();
                    $finder
                        ->files()
                        ->depth(2)
                        ->followLinks()
                        ->ignoreUnreadableDirs(true)
                        ->name($customFilename)
                        ->in($this->getVendorDir());

                    foreach ($finder as $file) {
                        /* @var $file SplFileInfo */
                        $this->registerPluginConfigFile($magentoRootFolder, $file);
                    }
                }
            }

            if (count($moduleBaseFolders) > 0) {
                // Glob plugin folders
                $finder = Finder::create();
                $finder
                    ->files()
                    ->depth(1)
                    ->followLinks()
                    ->ignoreUnreadableDirs(true)
                    ->name($customFilename)
                    ->in($moduleBaseFolders);

                foreach ($finder as $file) {
                    /* @var $file SplFileInfo */
                    $this->registerPluginConfigFile($magentoRootFolder, $file);
                }
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_pluginConfig);

        return $config;
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
        if (BinaryString::startsWith($file->getPathname(), 'vfs://')) {
            $path = $file->getPathname();
        } else {
            $path = $file->getRealPath();

            if ($path === "") {
                throw new \UnexpectedValueException(sprintf("Realpath for '%s' did return an empty string.", $file));
            }

            if ($path === false) {
                $this->log(sprintf("<error>Plugin config file broken link '%s'</error>", $file));

                return;
            }
        }

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
        /* old vendor folder to give backward compatibility */
        $vendorFolder = $this->getConfigurationLoaderDir() . '/../../../../vendor';
        if (is_dir($vendorFolder)) {
            return $vendorFolder;
        }

        /* correct vendor folder for composer installations */
        $vendorFolder = $this->getConfigurationLoaderDir() . '/../../../../../../../vendor';
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
