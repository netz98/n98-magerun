<?php

namespace N98\Magento\Command;

use Symfony\Component\Yaml\Yaml;

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
        $config = $this->loadBundleConfig($config);
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
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @see http://packages.zendframework.com/docs/latest/manual/en/index.html#zend-stdlib
     * @param  array $a
     * @param  array $b
     * @return array
     */
    public function mergeArrays(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->mergeArrays($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
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
        $config = $this->mergeArrays($config, $initConfig);

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
            $config = $this->mergeArrays($config, $systemConfig);

            return $config;
        }

        return $config;
    }

    /**
     * Load config from all installed bundles
     *
     * @param array $config
     *
     * @return array
     */
    public function loadBundleConfig($config)
    {
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
            $config = $this->mergeArrays($config, $personalConfig);
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
            $config = $this->mergeArrays($config, $projectConfig);
            return $config;
        }
        return $config;
    }

}
