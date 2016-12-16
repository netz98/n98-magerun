<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use InvalidArgumentException;
use N98\Util\ArrayFunctions;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigFileParser
 *
 * @package N98\Magento\Application
 */
class ConfigFile
{
    /**
     * @var string
     */
    private $buffer;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     * @return ConfigFile
     * @throws InvalidArgumentException if $path is invalid (can't be read for whatever reason)
     */
    public static function createFromFile($path)
    {
        $configFile = new static();
        $configFile->loadFile($path);

        return $configFile;
    }

    /**
     * @param string $path
     */
    public function loadFile($path)
    {
        $this->path = $path;

        if (
            'data://' !== substr($path, 0, 7)
            && !is_readable($path)
        ) {
            throw new InvalidArgumentException(sprintf("Config-file is not readable: '%s'", $path));
        }

        $buffer = file_get_contents($path);
        if (!is_string($buffer)) {
            throw new InvalidArgumentException(sprintf("Fail while reading config-file: '%s'", $path));
        }

        $this->setBuffer($buffer);
    }

    /**
     * @param string $buffer
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @param string $magentoRootFolder
     * @param null|SplFileInfo $file [optional]
     *
     * @return void
     */
    public function applyVariables($magentoRootFolder, SplFileInfo $file = null)
    {
        $replace = array(
            '%module%' => $file ? $file->getPath() : '',
            '%root%'   => $magentoRootFolder,
        );

        $this->buffer = strtr($this->buffer, $replace);
    }

    /**
     * @throws RuntimeException
     */
    public function toArray()
    {
        $result = Yaml::parse($this->buffer);

        if (!is_array($result)) {
            throw new RuntimeException(sprintf("Failed to parse config-file '%s'", $this->path));
        }

        return $result;
    }

    public function mergeArray(array $array)
    {
        $result = $this->toArray();

        return ArrayFunctions::mergeArrays($array, $result);
    }
}
