<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

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
 */
class ConfigLocator
{
    /**
     * @var string
     */
    private $customConfigFilename;

    /**
     * @var string
     */
    private $magentoRootFolder;

    /**
     * ConfigLocator constructor.
     * @param string $configFilename
     * @param string $magentoRootFolder
     */
    public function __construct($configFilename, $magentoRootFolder)
    {
        $this->customConfigFilename = $configFilename;
        $this->magentoRootFolder = $magentoRootFolder;
    }

    /**
     * Obtain the user-config-file, it is placed in the homedir, e.g. ~/.n98-magerun2.yaml
     *
     * @return ConfigFile|null
     */
    public function getUserConfigFile()
    {
        $personalConfigFilePath = $this->getUserConfigFilePath();

        try {
            $userConfigFile = ConfigFile::createFromFile($personalConfigFilePath);
            $userConfigFile->applyVariables($this->magentoRootFolder);
        } catch (InvalidArgumentException $e) {
            $userConfigFile = null;
        }

        return $userConfigFile;
    }

    /**
     * Obtain the project-config-file, it is placed in the magento app/etc dir, e.g. app/etc/n98-magerun2.yaml
     *
     * @return ConfigFile|null
     */
    public function getProjectConfigFile()
    {
        if (!strlen($this->magentoRootFolder)) {
            return null;
        }

        $projectConfigFilePath = $this->magentoRootFolder . '/app/etc/' . $this->customConfigFilename;

        try {
            $projectConfigFile = ConfigFile::createFromFile($projectConfigFilePath);
            $projectConfigFile->applyVariables($this->magentoRootFolder);
        } catch (InvalidArgumentException $e) {
            $projectConfigFile = null;
        }

        return $projectConfigFile;
    }

    /**
     * Obtain the (optional) stop-file-config-file, it is placed in the folder of the stop-file, always
     * prefixed with a dot: stop-file-folder/.n98-magerun2.yaml
     *
     * @param string $magerunStopFileFolder
     * @return ConfigFile|null
     */
    public function getStopFileConfigFile($magerunStopFileFolder)
    {
        if (empty($magerunStopFileFolder)) {
            return null;
        }

        $stopFileConfigFilePath = $magerunStopFileFolder . '/.' . $this->customConfigFilename;

        if (!file_exists($stopFileConfigFilePath)) {
            return null;
        }

        try {
            $stopFileConfigFile = ConfigFile::createFromFile($stopFileConfigFilePath);
            $stopFileConfigFile->applyVariables($this->magentoRootFolder);
        } catch (InvalidArgumentException $e) {
            $stopFileConfigFile = null;
        }

        return $stopFileConfigFile;
    }

    /**
     * @return string
     */
    private function getUserConfigFilePath()
    {
        $homeDirectory = OperatingSystem::getHomeDir();
        if (!$homeDirectory) {
            throw new RuntimeException('Unable to get home-directory to obtain user-config-file.');
        }

        if (!is_dir($homeDirectory)) {
            throw new RuntimeException(sprintf("Home-directory '%s' is not a directory.", $homeDirectory));
        }

        $basename = $this->customConfigFilename;
        if (!OperatingSystem::isWindows()) {
            $basename = ".$basename";
        }

        return $homeDirectory . DIRECTORY_SEPARATOR . $basename;
    }
}
