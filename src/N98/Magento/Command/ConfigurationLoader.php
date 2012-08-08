<?php

namespace N98\Magento\Command;

use Symfony\Component\Yaml\Yaml;

class ConfigurationLoader
{
    /**
     * @var array
     */
    protected $_configArray;

    public function __construct()
    {
        $globalConfig = Yaml::parse(__DIR__ . '/../../../../config.yaml');
        $personalConfigFile = $_SERVER['HOME'] . '/.n98-magerun.yaml';
        if (file_exists($personalConfigFile)) {
            $personalConfig = Yaml::parse($personalConfigFile);
        }
        $this->_configArray = $this->mergeArrays($globalConfig, $personalConfig);
    }

    /**
     * @param array $a1
     * @param array $a2
     * @return array
     */
    protected function mergeArrays(array $a1, array $a2)
    {
        foreach($a2 as $key => $Value) {
            if (array_key_exists($key, $a1) && is_array($Value)) {
                $a1[$key] = $this->mergeArrays($a1[$key], $a2[$key]);
            } else {
                $a1[$key] = $Value;
            }
        }

        return $a1;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_configArray;
    }

}