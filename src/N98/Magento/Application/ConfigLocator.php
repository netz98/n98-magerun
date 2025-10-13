<?php

declare(strict_types=1);

namespace N98\Magento\Application;

use InvalidArgumentException;
use N98\Util\OperatingSystem;
use RuntimeException;

/**
 * Class ConfigLocator
 *
 * Has all the information encoded to retrieve the various config files
 *
 * @package N98\Magento\Application
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class ConfigLocator
{
    private string $customConfigFilename;

    private ?string $magentoRootFolder;

    public function __construct(string $configFilename, string $magentoRootFolder)
    {
        $this->customConfigFilename = $configFilename;
        $this->magentoRootFolder    = $magentoRootFolder;
    }

    /**
     * Obtain the user-config-file, it is placed in the homedir, e.g. ~/.n98-magerun2.yaml
     */
    public function getUserConfigFile(): ?ConfigFile
    {
        $userConfigFile = null;

        $personalConfigFilePaths = $this->getUserConfigFilePaths();

        foreach ($personalConfigFilePaths as $personalConfigFilePath) {
            try {
                $userConfigFile = ConfigFile::createFromFile($personalConfigFilePath);
                $userConfigFile->applyVariables($this->magentoRootFolder);
                break;
            } catch (InvalidArgumentException $e) {
                $userConfigFile = null;
            }
        }

        return $userConfigFile;
    }

    /**
     * Obtain the project-config-file, it is placed in the magento app/etc dir, e.g. app/etc/n98-magerun2.yaml
     */
    public function getProjectConfigFile(): ?ConfigFile
    {
        if ($this->magentoRootFolder === '') {
            return null;
        }

        $projectConfigFilePath = $this->magentoRootFolder . '/app/etc/' . $this->customConfigFilename;
        if (!is_readable($projectConfigFilePath)) {
            return null;
        }

        try {
            $projectConfigFile = ConfigFile::createFromFile($projectConfigFilePath);
            $projectConfigFile->applyVariables($this->magentoRootFolder);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $projectConfigFile = null;
        }

        return $projectConfigFile;
    }

    /**
     * Obtain the (optional) stop-file-config-file, it is placed in the folder of the stop-file, always
     * prefixed with a dot: stop-file-folder/.n98-magerun2.yaml
     */
    public function getStopFileConfigFile(string $magerunStopFileFolder): ?ConfigFile
    {
        if ($magerunStopFileFolder === '' || $magerunStopFileFolder === '0') {
            return null;
        }

        $stopFileConfigFilePath = $magerunStopFileFolder . '/.' . $this->customConfigFilename;

        if (!file_exists($stopFileConfigFilePath)) {
            return null;
        }

        try {
            $stopFileConfigFile = ConfigFile::createFromFile($stopFileConfigFilePath);
            $stopFileConfigFile->applyVariables($this->magentoRootFolder);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $stopFileConfigFile = null;
        }

        return $stopFileConfigFile;
    }

    /**
     * @return string[]
     */
    private function getUserConfigFilePaths(): array
    {
        $paths = [];

        $homeDirectory = OperatingSystem::getHomeDir();
        if ($homeDirectory === false || $homeDirectory === '') {
            return $paths;
        }

        if (!is_dir($homeDirectory)) {
            throw new RuntimeException(sprintf("Home-directory '%s' is not a directory.", $homeDirectory));
        }

        $basename = $this->customConfigFilename;

        if (OperatingSystem::isWindows()) {
            $paths[] = $homeDirectory . '/' . $basename;
        }

        $paths[] = $homeDirectory . '/.' . $basename;

        return $paths;
    }
}
