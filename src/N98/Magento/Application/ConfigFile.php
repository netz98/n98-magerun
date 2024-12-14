<?php

declare(strict_types=1);

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
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
final class ConfigFile
{
    private string $buffer;

    private string $path;

    /**
     * @throws InvalidArgumentException if $path is invalid (can't be read for whatever reason)
     */
    public static function createFromFile(string $path): ConfigFile
    {
        $static = new self();
        $static->loadFile($path);

        return $static;
    }

    public function loadFile(string $path): void
    {
        $this->path = $path;

        if ('data://' !== substr($path, 0, 7)
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

    public function setBuffer(string $buffer): void
    {
        $this->buffer = $buffer;
    }

    public function applyVariables(string $magentoRootFolder, ?SplFileInfo $file = null): void
    {
        $replace = ['%module%' => $file instanceof SplFileInfo ? $file->getPath() : '', '%root%'   => $magentoRootFolder];

        $this->buffer = strtr($this->buffer, $replace);
    }

    /**
     * @throws RuntimeException
     */
    public function toArray(): array
    {
        $result = Yaml::parse($this->buffer);

        if (!is_array($result)) {
            throw new RuntimeException(sprintf("Failed to parse config-file '%s'", $this->path));
        }

        return $result;
    }

    public function mergeArray(array $array): array
    {
        $result = $this->toArray();
        return ArrayFunctions::mergeArrays($array, $result);
    }

    /**
     * @return string path to config-file
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
