<?php

namespace N98\Magento\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use N98\Util\ArrayFunctions;

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
     * Load config
     * If $magentoRootFolder is null, only non-project config is loaded
     *
     * @param array $config
     * @param string $magentoRootFolder
     */
    public function __construct($config)
    {
        $this->_initialConfig = $config;
    }

    public function getPartialConfig()
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        $config = $this->loadSystemConfig($config);
        $config = $this->loadUserConfig($config);
        return $config;
    }

    public function loadStageTwo($magentoRootFolder)
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        $config = $this->loadPluginConfig($config, $magentoRootFolder);
        $config = $this->loadSystemConfig($config);
        $config = $this->loadUserConfig($config, $magentoRootFolder);
        $config = $this->loadProjectConfig($magentoRootFolder, $config);
        $config = $this->initAutoloaders($magentoRootFolder, $config);
        $this->_configArray = $config;
    }

    /**
     * @param $magentoRootFolder
     * @param $config
     * @return mixed
     */
    protected function initAutoloaders($magentoRootFolder, $config)
    {
        if (isset($config['autoloaders']) && is_array($config['autoloaders'])) {
            foreach ($config['autoloaders'] as &$value) {
                $value = str_replace('%root%', $magentoRootFolder, $value);
            }
        }
        return $config;
    }

    /**
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
    protected function loadDistConfig($initConfig)
    {
        if ($this->_distConfig == null) {
            $this->_distConfig = Yaml::parse(__DIR__ . '/../../../../config.yaml');
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
    public function loadSystemConfig($config)
    {
        if ($this->_systemConfig == null) {
            $systemWideConfigFile = '/etc/' . $this->_customConfigFilename;
            if ($systemWideConfigFile && file_exists($systemWideConfigFile)) {
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
     * @param array  $config
     * @param string $magentoRootFolder
     *
     * @return array
     */
    public function loadPluginConfig($config, $magentoRootFolder)
    {
        if ($this->_pluginConfig == null) {
            $this->_pluginConfig = array();
            $moduleBaseFolders = array();
            $config['plugin']['folders'][] = getenv('HOME') . '/.n98-magerun/modules';
            $config['plugin']['folders'][] = $magentoRootFolder . '/lib/n98-magerun/modules';
            foreach ($config['plugin']['folders'] as $folder) {
                if (is_dir($folder)) {
                    $moduleBaseFolders[] = $folder;
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
                    ->name('n98-magerun.yaml')
                    ->in($moduleBaseFolders);

                foreach ($finder as $file) { /* @var $file \Symfony\Component\Finder\SplFileInfo */
                    $localPluginConfig = \file_get_contents($file->getRealPath());
                    $localPluginConfig = Yaml::parse($this->applyVariables($localPluginConfig, $magentoRootFolder, $file));
                    $this->_pluginConfig = ArrayFunctions::mergeArrays($this->_pluginConfig, $localPluginConfig);
                }
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_pluginConfig);

        return $config;
    }

    /**
     * @param string                                $rawConfig
     * @param string                                $magentoRootFolder
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return string
     */
    protected function applyVariables($rawConfig, $magentoRootFolder, $file = null)
    {
        $replace = array(
            '%module%' => $file ? $file->getPath() : '',
            '%root%'   => $magentoRootFolder,
        );

        return str_replace(array_keys($replace), $replace, $rawConfig);
    }


    /**
     * Check if there is a user config file. ~/.n98-magerun.yaml
     *
     * @param array  $config
     * @param string $magentoRootFolder
     *
     * @return array
     */
    public function loadUserConfig($config, $magentoRootFolder = null)
    {
        if ($this->_userConfig == null) {
            $this->_userConfig = array();
            $homeDirectory = getenv('HOME');
            $personalConfigFile = $homeDirectory . DIRECTORY_SEPARATOR . '.' . $this->_customConfigFilename;
            if ($homeDirectory && file_exists($personalConfigFile)) {
                $userConfig = $this->applyVariables(\file_get_contents($personalConfigFile), $magentoRootFolder, null);
                $this->_userConfig = Yaml::parse($userConfig);

                return $config;
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_userConfig);

        return $config;
    }

    /**
     * MAGENTO_ROOT/app/etc/n98-magerun.yaml
     *
     * @param string $magentoRootFolder
     * @param array $config
     *
     * @return array
     */
    public function loadProjectConfig($magentoRootFolder, $config)
    {
        if ($this->_projectConfig == null) {
            $this->_projectConfig = array();
            $projectConfigFile = $magentoRootFolder . DIRECTORY_SEPARATOR . 'app/etc/' . $this->_customConfigFilename;
            if ($projectConfigFile && file_exists($projectConfigFile)) {
                $projectConfig = $this->applyVariables(\file_get_contents($projectConfigFile), $magentoRootFolder, null);
                $this->_projectConfig = Yaml::parse($projectConfig);
                return $config;
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_projectConfig);
        return $config;
    }

}