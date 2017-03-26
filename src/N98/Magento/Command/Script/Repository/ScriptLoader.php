<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Util\OperatingSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ScriptLoader
{
    /**
     * @var string
     */
    private $homeDir;

    /**
     * @var array
     */
    protected $_scriptFiles = array();

    /**
     * @var string
     * @deprecated since 1.97.29
     */
    protected $_homeScriptFolder = '';

    /**
     * @var string
     */
    protected $_magentoRootFolder = '';

    /**
     * @var array
     */
    protected $_scriptFolders = array();

    /**
     * @param array  $scriptFolders
     * @param string $magentoRootFolder
     */
    public function __construct(array $scriptFolders, $magentoRootFolder = null)
    {
        $this->homeDir = OperatingSystem::getHomeDir();

        $this->_magentoRootFolder = $magentoRootFolder;

        if (OperatingSystem::isWindows()) {
            $scriptFolders[] = $this->homeDir . '/n98-magerun/scripts';
        }
        $scriptFolders[] = $this->homeDir . '/.n98-magerun/scripts';

        $this->findScripts($scriptFolders);
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->_scriptFiles;
    }

    protected function findScripts(array $scriptFolders = null)
    {
        if (null === $scriptFolders) {
            $scriptFolders = $this->_scriptFolders;
        }

        $scriptFolders = array_filter(array_filter($scriptFolders, 'strlen'), 'is_dir');

        $this->_scriptFolders = $scriptFolders;
        $this->_scriptFiles = array();
        if (1 > count($scriptFolders)) {
            return;
        }

        $finder = Finder::create()
            ->files()
            ->followLinks(true)
            ->ignoreUnreadableDirs(true)
            ->name('*.magerun')
            ->in($scriptFolders);

        $scriptFiles = array();
        foreach ($finder as $file) { /* @var $file SplFileInfo */
            $scriptFiles[$file->getFilename()] = array(
                'fileinfo'    => $file,
                'description' => $this->_readFirstLineOfFile($file->getPathname()),
                'location'    => $this->_getLocation($file->getPathname()),
            );
        }

        ksort($scriptFiles);
        $this->_scriptFiles = $scriptFiles;
    }

    /**
     * Reads the first line. If it's a comment return it.
     *
     * @param string $file
     *
     * @return string
     */
    protected function _readFirstLineOfFile($file)
    {
        $f = @fopen($file, 'r');
        if (!$f) {
            return '';
        }
        $line = trim(fgets($f));
        fclose($f);

        if (isset($line[0]) && $line[0] != '#') {
            return '';
        }

        return trim(substr($line, 1));
    }

    /**
     * @param string $pathname
     *
     * @return string
     */
    protected function _getLocation($pathname)
    {
        if (strstr($pathname, $this->_magentoRootFolder)) {
            return 'project';
        }

        if (strstr($pathname, $this->homeDir)) {
            return 'personal';
        }

        if (strstr($pathname, 'n98-magerun/modules')) {
            return 'module';
        }

        return 'system';
    }
}
