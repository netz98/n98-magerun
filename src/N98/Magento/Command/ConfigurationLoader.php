<?php

namespace N98\Magento\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use N98\Util\ArrayFunctions;

class ConfigurationLoader
{
    /**
     * @var array
     */
    protected $_configArray;

    /**
     * @var string
     */
    protected $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * @param array $config
     * @param string $magentoRootFolder
     */
    public function __construct($config, $magentoRootFolder)
    {
        $config = $this->loadDistConfig($config);
        $config = $this->loadPluginConfig($config, $magentoRootFolder);
        $config = $this->loadSystemConfig($config);
        $config = $this->loadUserConfig($config);
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
        return $this->_configArray;
    }

    /**
     * @param array $initConfig
     *
     * @return array
     */
    protected function loadDistConfig($initConfig)
    {
        $config = Yaml::parse(__DIR__ . '/../../../../config.yaml');
        $config = ArrayFunctions::mergeArrays($config, $initConfig);

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
        $systemWideConfigFile = '/etc/' . $this->_customConfigFilename;
        if ($systemWideConfigFile && file_exists($systemWideConfigFile)) {
            $systemConfig = Yaml::parse($systemWideConfigFile);
            $config = ArrayFunctions::mergeArrays($config, $systemConfig);

            return $config;
        }

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
        // Glob plugin folders
        $finder = new Finder();
        $finder
            ->files()
            ->depth(1)
            ->name('n98-magerun.yaml')
            ->in($config['plugin']['folders']);

        foreach ($finder as $file) { /* @var $file \Symfony\Component\Finder\SplFileInfo */
            $moduleConfig = Yaml::parse($file->getRealPath());

            if (isset($moduleConfig['autoloaders'])) {
                foreach ($moduleConfig['autoloaders'] as &$value) {
                    $replace = array(
                        '%module%' => $file->getPath(),
                    );

                    $value = str_replace(array_keys($replace), $replace, $value);
                }
            }

            $config = ArrayFunctions::mergeArrays($config, $moduleConfig);
        }

        return $config;
    }

    /**
     * Check if there is a user config file. ~/.n98-magerun.yaml
     *
     * @param array $config
     *
     * @return array
     */
    public function loadUserConfig($config)
    {
        $homeDirectory = getenv('HOME');
        $personalConfigFile = $homeDirectory . DIRECTORY_SEPARATOR . '.' . $this->_customConfigFilename;
        if ($homeDirectory && file_exists($personalConfigFile)) {
            $personalConfig = Yaml::parse($personalConfigFile);
            $config = ArrayFunctions::mergeArrays($config, $personalConfig);
            return $config;
        }
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
        $projectConfigFile = $magentoRootFolder . DIRECTORY_SEPARATOR . 'app/etc/' . $this->_customConfigFilename;
        if ($projectConfigFile && file_exists($projectConfigFile)) {
            $projectConfig = Yaml::parse($projectConfigFile);
            $config = ArrayFunctions::mergeArrays($config, $projectConfig);
            return $config;
        }
        return $config;
    }

}