<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Util\OperatingSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ScriptLoader
 *
 * @package N98\Magento\Command\Script\Repository
 */
class ScriptLoader
{
    /**
     * @var string|false
     */
    private $homeDir;

    protected array $_scriptFiles = [];

    protected ?string $_magentoRootFolder = '';

    protected array $_scriptFolders = [];

    public function __construct(array $scriptFolders, string $magentoRootFolder = '')
    {
        $this->homeDir = OperatingSystem::getHomeDir();

        $this->_magentoRootFolder = $magentoRootFolder;

        if (OperatingSystem::isWindows()) {
            $scriptFolders[] = $this->homeDir . '/n98-magerun/scripts';
        }

        $scriptFolders[] = $this->homeDir . '/.n98-magerun/scripts';

        $this->findScripts($scriptFolders);
    }

    public function getFiles(): array
    {
        return $this->_scriptFiles;
    }

    protected function findScripts(?array $scriptFolders = null): void
    {
        if (null === $scriptFolders) {
            $scriptFolders = $this->_scriptFolders;
        }

        $scriptFolders = array_filter($scriptFolders, function ($value): bool {
            return (string) $value !== '';
        });
        $scriptFolders = array_filter($scriptFolders, 'is_dir');

        $this->_scriptFolders = $scriptFolders;
        $this->_scriptFiles = [];
        if (1 > count($scriptFolders)) {
            return;
        }

        $finder = Finder::create()
            ->files()->followLinks()
            ->ignoreUnreadableDirs(true)
            ->name('*.magerun')
            ->in($scriptFolders);

        $scriptFiles = [];
        foreach ($finder as $file) { /* @var SplFileInfo $file */
            $scriptFiles[$file->getFilename()] = ['fileinfo'    => $file, 'description' => $this->_readFirstLineOfFile($file->getPathname()), 'location'    => $this->_getLocation($file->getPathname())];
        }

        ksort($scriptFiles);
        $this->_scriptFiles = $scriptFiles;
    }

    /**
     * Reads the first line. If it's a comment return it.
     */
    protected function _readFirstLineOfFile(string $file): string
    {
        $fopen = @fopen($file, 'r');
        if (!$fopen) {
            return '';
        }

        $line = trim((string) fgets($fopen));
        fclose($fopen);

        if (isset($line[0]) && $line[0] !== '#') {
            return '';
        }

        return trim(substr($line, 1));
    }

    protected function _getLocation(string $pathname): string
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
