<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Util\OperatingSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Script loader
 *
 * @package N98\Magento\Command\Script\Repository
 */
class ScriptLoader
{
    /**
     * @var string|false
     */
    private $homeDir;

    /**
     * @var array<string, array{fileinfo: SplFileInfo, description: string, location: string}>
     */
    protected array $_scriptFiles = [];

    /**
     * @var string
     * @deprecated since 1.97.29
     */
    protected string $_homeScriptFolder = '';

    /**
     * @var string|null
     */
    protected ?string $_magentoRootFolder = '';

    /**
     * @var array<int, string>
     */
    protected array $_scriptFolders = [];

    /**
     * @param array<int, string> $scriptFolders
     * @param string|null $magentoRootFolder
     */
    public function __construct(array $scriptFolders, ?string $magentoRootFolder = null)
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
     * @return array<string, array{fileinfo: SplFileInfo, description: string, location: string}>
     */
    public function getFiles(): array
    {
        return $this->_scriptFiles;
    }

    /**
     * @param array<int, string>|null $scriptFolders
     * @return void
     */
    protected function findScripts(?array $scriptFolders = null): void
    {
        if (null === $scriptFolders) {
            $scriptFolders = $this->_scriptFolders;
        }

        /** @phpstan-ignore argument.type (@TODO(sr)) */
        $scriptFolders = array_filter(array_filter($scriptFolders, 'strlen'), 'is_dir');

        $this->_scriptFolders = $scriptFolders;
        $this->_scriptFiles = [];
        if (1 > count($scriptFolders)) {
            return;
        }

        $finder = Finder::create()
            ->files()->followLinks()
            ->ignoreUnreadableDirs()
            ->name('*.magerun')
            ->in($scriptFolders);

        $scriptFiles = [];
        foreach ($finder as $file) {
            /* @var SplFileInfo $file */
            $scriptFiles[$file->getFilename()] = [
                'fileinfo'    => $file,
                'description' => $this->_readFirstLineOfFile($file->getPathname()),
                'location'    => $this->_getLocation($file->getPathname())
            ];
        }

        ksort($scriptFiles);
        $this->_scriptFiles = $scriptFiles;
    }

    /**
     * Reads the first line. If it's a comment return it.
     *
     * @param string $file
     * @return string
     */
    protected function _readFirstLineOfFile(string $file): string
    {
        $f = @fopen($file, 'r');
        if (!$f) {
            return '';
        }

        $line = trim((string)fgets($f));
        fclose($f);

        if (isset($line[0]) && $line[0] != '#') {
            return '';
        }

        return trim(substr($line, 1));
    }

    /**
     * @param string $pathname
     * @return string
     */
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
